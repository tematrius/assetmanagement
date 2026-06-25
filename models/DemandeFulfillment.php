<?php

declare(strict_types=1);

class DemandeFulfillment extends Model
{
    public function summary(array $request): array
    {
        $requestId = (int) $request['id'];
        $individualAssignments = $this->individualAssignments($requestId);
        $stockAssignments = $this->stockAssignments($requestId);

        $requestedAccessories = [];
        foreach (($request['accessoires'] ?? []) as $accessory) {
            if (!is_array($accessory)) {
                continue;
            }
            $categoryId = (int) ($accessory['categorie_id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }
            $requestedAccessories[$categoryId] = [
                'categorie_id' => $categoryId,
                'label' => (string) ($accessory['label'] ?? 'Accessoire'),
                'requested' => max(1, (int) ($accessory['quantite'] ?? 1)),
                'assigned' => 0,
            ];
        }

        foreach ($stockAssignments as $assignment) {
            $categoryId = (int) $assignment['categorie_id'];
            if (isset($requestedAccessories[$categoryId])) {
                $requestedAccessories[$categoryId]['assigned'] += (int) $assignment['quantite'];
            }
        }

        foreach ($requestedAccessories as &$accessory) {
            $accessory['remaining'] = max(0, $accessory['requested'] - $accessory['assigned']);
            $accessory['stocks'] = $accessory['remaining'] > 0
                ? $this->availableStocksByCategory((int) $accessory['categorie_id'], (int) $accessory['remaining'])
                : [];
        }
        unset($accessory);

        $needsIndividual = (int) ($request['categorie_id'] ?? 0) > 0;
        $individualComplete = !$needsIndividual || $individualAssignments !== [];
        $accessoriesComplete = array_reduce(
            $requestedAccessories,
            static fn (bool $complete, array $item): bool => $complete && (int) $item['remaining'] === 0,
            true
        );

        return [
            'individualAssignments' => $individualAssignments,
            'stockAssignments' => $stockAssignments,
            'availableEquipments' => $needsIndividual && !$individualComplete
                ? $this->availableEquipmentsByCategory((int) $request['categorie_id'])
                : [],
            'accessories' => array_values($requestedAccessories),
            'needsIndividual' => $needsIndividual,
            'individualComplete' => $individualComplete,
            'accessoriesComplete' => $accessoriesComplete,
            'complete' => $individualComplete && $accessoriesComplete,
            'hasAssignments' => $individualAssignments !== [] || $stockAssignments !== [],
        ];
    }

    public function fulfill(int $requestId, array $data): array
    {
        $this->db->beginTransaction();
        try {
            $request = $this->lockRequest($requestId);
            if (!in_array((string) $request['statut'], ['approuve', 'attribue'], true)) {
                throw new RuntimeException('Cette demande ne peut pas etre attribuee dans son statut actuel.');
            }

            $payload = json_decode((string) $request['justification'], true);
            if (!is_array($payload)) {
                $payload = [];
            }
            $request['accessoires'] = is_array($payload['accessoires'] ?? null) ? $payload['accessoires'] : [];
            $assignmentCountBefore = $this->assignmentCount($requestId);

            $equipmentId = (int) ($data['equipement_id'] ?? 0);
            if ((int) ($request['categorie_id'] ?? 0) > 0 && !$this->hasIndividualAssignment($requestId) && $equipmentId > 0) {
                $this->assignEquipment($request, $equipmentId, trim((string) ($data['commentaire'] ?? '')));
            }

            $stockSelections = is_array($data['stock_id'] ?? null) ? $data['stock_id'] : [];
            $remainingByCategory = $this->remainingAccessories($requestId, $request['accessoires']);
            foreach ($remainingByCategory as $categoryId => $item) {
                $remaining = (int) $item['remaining'];
                $stockId = (int) ($stockSelections[$categoryId] ?? 0);
                if ($remaining <= 0 || $stockId <= 0) {
                    continue;
                }
                $this->assignStock(
                    $request,
                    $stockId,
                    (int) $categoryId,
                    $remaining,
                    trim((string) ($data['commentaire'] ?? ''))
                );
            }

            if ($this->assignmentCount($requestId) === $assignmentCountBefore) {
                throw new InvalidArgumentException('Selectionne au moins un element disponible a attribuer.');
            }

            $state = $this->completionState($requestId, $request);
            $nextStatus = $state['complete'] ? 'cloture' : ($state['hasAssignments'] ? 'attribue' : 'approuve');
            $this->db->prepare('UPDATE demandes SET statut = :statut WHERE id = :id')
                ->execute(['statut' => $nextStatus, 'id' => $requestId]);

            $this->db->commit();
            return ['status' => $nextStatus] + $state;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function assignEquipment(array $request, int $equipmentId, string $comment): void
    {
        $stmt = $this->db->prepare(
            "SELECT id, categorie_id, statut FROM equipements WHERE id = :id FOR UPDATE"
        );
        $stmt->execute(['id' => $equipmentId]);
        $equipment = $stmt->fetch();
        if (
            !$equipment
            || (int) $equipment['categorie_id'] !== (int) $request['categorie_id']
            || (string) $equipment['statut'] !== 'disponible'
        ) {
            throw new RuntimeException('L equipement selectionne n est plus disponible pour cette demande.');
        }

        $note = $comment !== '' ? $comment : 'Attribution depuis la demande #' . (int) $request['id'];
        $this->db->prepare(
            "INSERT INTO attributions
                (demande_id, equipement_id, utilisateur_id, quantite, statut, commentaire, attribue_par)
             VALUES (:demande_id, :equipement_id, :utilisateur_id, 1, 'active', :commentaire, :attribue_par)"
        )->execute([
            'demande_id' => (int) $request['id'],
            'equipement_id' => $equipmentId,
            'utilisateur_id' => (int) $request['demandeur_id'],
            'commentaire' => $note,
            'attribue_par' => $this->actorId(),
        ]);
        $this->db->prepare("UPDATE equipements SET statut = 'attribue' WHERE id = :id")
            ->execute(['id' => $equipmentId]);
        $this->insertHistory($request, $equipmentId, null, 1, $note);
    }

    private function assignStock(array $request, int $stockId, int $categoryId, int $quantity, string $comment): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, categorie_id, quantite_disponible FROM stocks_quantitatifs WHERE id = :id FOR UPDATE'
        );
        $stmt->execute(['id' => $stockId]);
        $stock = $stmt->fetch();
        if (
            !$stock
            || (int) $stock['categorie_id'] !== $categoryId
            || (int) $stock['quantite_disponible'] < $quantity
        ) {
            throw new RuntimeException('Le stock selectionne est insuffisant ou ne correspond plus a l accessoire.');
        }

        $note = $comment !== '' ? $comment : 'Attribution depuis la demande #' . (int) $request['id'];
        $this->db->prepare(
            'UPDATE stocks_quantitatifs
             SET quantite_disponible = quantite_disponible - :quantite_retrait,
                 quantite_attribuee = quantite_attribuee + :quantite_ajout
             WHERE id = :id'
        )->execute([
            'quantite_retrait' => $quantity,
            'quantite_ajout' => $quantity,
            'id' => $stockId,
        ]);
        $this->db->prepare(
            "INSERT INTO attributions
                (demande_id, stock_quantitatif_id, utilisateur_id, quantite, statut, commentaire, attribue_par)
             VALUES (:demande_id, :stock_id, :utilisateur_id, :quantite, 'active', :commentaire, :attribue_par)"
        )->execute([
            'demande_id' => (int) $request['id'],
            'stock_id' => $stockId,
            'utilisateur_id' => (int) $request['demandeur_id'],
            'quantite' => $quantity,
            'commentaire' => $note,
            'attribue_par' => $this->actorId(),
        ]);
        $this->insertHistory($request, null, $stockId, $quantity, $note);
    }

    private function insertHistory(
        array $request,
        ?int $equipmentId,
        ?int $stockId,
        int $quantity,
        string $comment
    ): void {
        $this->db->prepare(
            "INSERT INTO historique_equipements
                (equipement_id, stock_quantitatif_id, type_operation, utilisateur_destination_id,
                 source_type, source_label, destination_type, quantite, commentaire, effectue_par)
             VALUES
                (:equipement_id, :stock_id, 'attribution', :utilisateur_id,
                 'depot', 'Depot IT Central', 'utilisateur', :quantite, :commentaire, :effectue_par)"
        )->execute([
            'equipement_id' => $equipmentId,
            'stock_id' => $stockId,
            'utilisateur_id' => (int) $request['demandeur_id'],
            'quantite' => $quantity,
            'commentaire' => $comment,
            'effectue_par' => $this->actorId(),
        ]);
    }

    private function lockRequest(int $requestId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM demandes WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $requestId]);
        $request = $stmt->fetch();
        if (!$request) {
            throw new RuntimeException('Demande introuvable.');
        }
        return $request;
    }

    private function completionState(int $requestId, array $request): array
    {
        $hasIndividual = $this->hasIndividualAssignment($requestId);
        $needsIndividual = (int) ($request['categorie_id'] ?? 0) > 0;
        $remaining = $this->remainingAccessories($requestId, $request['accessoires'] ?? []);
        $accessoriesComplete = array_reduce(
            $remaining,
            static fn (bool $complete, array $item): bool => $complete && (int) $item['remaining'] === 0,
            true
        );
        $hasStockAssignments = $this->stockAssignments($requestId) !== [];
        return [
            'complete' => (!$needsIndividual || $hasIndividual) && $accessoriesComplete,
            'hasAssignments' => $hasIndividual || $hasStockAssignments,
        ];
    }

    private function remainingAccessories(int $requestId, array $accessories): array
    {
        $remaining = [];
        foreach ($accessories as $accessory) {
            if (!is_array($accessory)) {
                continue;
            }
            $categoryId = (int) ($accessory['categorie_id'] ?? 0);
            if ($categoryId > 0) {
                $remaining[$categoryId] = [
                    'requested' => max(1, (int) ($accessory['quantite'] ?? 1)),
                    'assigned' => 0,
                    'remaining' => 0,
                ];
            }
        }
        foreach ($this->stockAssignments($requestId) as $assignment) {
            $categoryId = (int) $assignment['categorie_id'];
            if (isset($remaining[$categoryId])) {
                $remaining[$categoryId]['assigned'] += (int) $assignment['quantite'];
            }
        }
        foreach ($remaining as &$item) {
            $item['remaining'] = max(0, $item['requested'] - $item['assigned']);
        }
        unset($item);
        return $remaining;
    }

    private function hasIndividualAssignment(int $requestId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM attributions
             WHERE demande_id = :demande_id AND equipement_id IS NOT NULL'
        );
        $stmt->execute(['demande_id' => $requestId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function assignmentCount(int $requestId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM attributions WHERE demande_id = :demande_id');
        $stmt->execute(['demande_id' => $requestId]);
        return (int) $stmt->fetchColumn();
    }

    private function individualAssignments(int $requestId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, e.serial_number, e.code_inventaire, e.designation, c.nom AS categorie_nom
             FROM attributions a
             JOIN equipements e ON e.id = a.equipement_id
             JOIN categories_equipements c ON c.id = e.categorie_id
             WHERE a.demande_id = :demande_id AND a.equipement_id IS NOT NULL
             ORDER BY a.date_attribution, a.id'
        );
        $stmt->execute(['demande_id' => $requestId]);
        return $stmt->fetchAll();
    }

    private function stockAssignments(int $requestId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, s.designation, s.categorie_id, c.nom AS categorie_nom
             FROM attributions a
             JOIN stocks_quantitatifs s ON s.id = a.stock_quantitatif_id
             JOIN categories_equipements c ON c.id = s.categorie_id
             WHERE a.demande_id = :demande_id AND a.stock_quantitatif_id IS NOT NULL
             ORDER BY a.date_attribution, a.id'
        );
        $stmt->execute(['demande_id' => $requestId]);
        return $stmt->fetchAll();
    }

    private function availableEquipmentsByCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, serial_number, code_inventaire, designation, marque, modele
             FROM equipements
             WHERE categorie_id = :categorie_id AND statut = 'disponible'
             ORDER BY serial_number, code_inventaire"
        );
        $stmt->execute(['categorie_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    private function availableStocksByCategory(int $categoryId, int $minimumQuantity): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, designation, emplacement, quantite_disponible
             FROM stocks_quantitatifs
             WHERE categorie_id = :categorie_id AND quantite_disponible >= :minimum
             ORDER BY quantite_disponible DESC, id'
        );
        $stmt->execute(['categorie_id' => $categoryId, 'minimum' => $minimumQuantity]);
        return $stmt->fetchAll();
    }

    private function actorId(): ?int
    {
        return Auth::id() > 0 ? Auth::id() : null;
    }
}
