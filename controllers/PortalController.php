<?php

declare(strict_types=1);

class PortalController extends Controller
{
    private Portal $portal;

    public function __construct()
    {
        $this->portal = new Portal();
    }

    public function equipment(): void
    {
        Auth::requireAuth();
        $this->view('portal/equipment', [
            'title' => 'Mon materiel',
            'items' => $this->portal->equipmentFor(Auth::id()),
        ]);
    }

    public function requests(): void
    {
        Auth::requireAuth();
        $allRequests = $this->portal->requestsFor(Auth::id());
        $this->view('portal/requests', [
            'title' => 'Mes demandes',
            'recentRequests' => array_slice($allRequests, 0, 3),
            'historyRequests' => array_slice($allRequests, 3, 7),
            'totalRequests' => count($allRequests),
            'pendingCount' => count(array_filter($allRequests, static fn (array $request): bool => in_array((string) $request['statut'], ['soumis', 'validation_responsable', 'validation_it', 'correction_requise'], true))),
            'approvedCount' => count(array_filter($allRequests, static fn (array $request): bool => in_array((string) $request['statut'], ['approuve', 'attribue', 'cloture'], true))),
            'archivedCount' => count(array_filter($allRequests, static fn (array $request): bool => in_array((string) $request['statut'], ['approuve', 'rejete', 'attribue', 'cloture'], true))),
        ]);
    }

    public function requestArchives(): void
    {
        Auth::requireAuth();
        $this->view('portal/request_archives', [
            'title' => 'Historique des demandes',
            'requests' => $this->portal->archivedRequestsFor(Auth::id()),
        ]);
    }

    public function validations(): void
    {
        Auth::requireAuth();
        if (!Auth::canValidate()) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $requests = $this->portal->validationQueue(Auth::id());
        $this->view('portal/validations', [
            'title' => 'Demandes a valider',
            'requests' => $requests,
            'urgentCount' => count(array_filter($requests, static fn (array $request): bool => (string) $request['urgence'] === 'haute')),
            'todayCount' => count(array_filter($requests, static fn (array $request): bool => date('Y-m-d', strtotime((string) $request['date_demande'])) === date('Y-m-d'))),
        ]);
    }

    public function profile(): void
    {
        Auth::requireAuth();
        $profile = $this->portal->profile(Auth::id());
        if (!$profile) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('portal/profile', [
            'title' => 'Mon profil',
            'profile' => $profile,
            'validators' => $this->portal->validatorsFor(Auth::id()),
        ]);
    }
}
