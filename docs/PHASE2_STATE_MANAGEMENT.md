# PHASE 2: Refactorisation de la Gestion des États d'Équipements

## Vue d'ensemble

Cette phase implémente une **séparation complète entre l'état réel (manuel) et l'état théorique (automatique)** des équipements, avec un système d'audit complet.

## Architecture Implémentée

### 1. Schéma Base de Données

#### Colonnes Ajoutées
- **`equipements.etat_theorique`**: ENUM('neuf','bon','moyen','mauvais','a_declasser') - Calculé automatiquement
- **`equipements.etat`**: Modifié pour supporter 5 états au lieu de 4 ('moyen' ajouté)

#### Tables d'Historique
- **`equipement_etat_historique`**:
  - `equipement_id` (FK)
  - `ancien_etat`, `nouvel_etat` (ENUM avec tous les 5 états)
  - `agent_username` (qui a effectué le changement)
  - `commentaire` (obligatoire, pour audit)
  - `created_at` (timestamp auto)

- **`stock_etat_historique`** (pour gestion quantités):
  - `stock_id` (FK)
  - `ancien_etat`, `nouvel_etat`
  - `quantite` (nombre d'items transférés)
  - `agent_username`, `commentaire`, `created_at`

### 2. Modèles de Données

#### Equipement.php - Nouvelles Méthodes
```php
// Changer l'état avec audit mandatory
changeEtat(int $equipementId, string $nouvelEtat, string $commentaire, string $agentUsername): bool

// Récupérer l'historique des changements
getEtatHistorique(int $equipementId): array

// Calculer l'état théorique basé sur l'âge
calculateTheoreticalState(?array $equipement): string
// Mapping:
// 0-1 an     → 'neuf'
// 1-3 ans    → 'bon'
// 3-5 ans    → 'moyen'
// 5+ ans     → 'mauvais'

// Mettre à jour tous les états théoriques
updateAllTheoreticalStates(): int
```

#### Stock.php - Méthodes Équivalentes
```php
// Pour quantités
changeEtatQuantite(int $stockId, string $ancienEtat, string $nouvelEtat, 
                   int $quantite, string $commentaire, string $agentUsername): bool

getEtatHistoriqueStock(int $stockId): array
calculateTheoreticalState(?array $stock): string
updateAllTheoreticalStates(): int
```

### 3. Helpers (config/helpers.php)

Trois nouvelles fonctions d'affichage:
```php
state_badge_class(string $state): string
// Retourne la classe CSS Bootstrap pour le badge (bg-success, bg-info, etc.)

state_label(string $state): string
// Retourne le label français (Neuf, Bon, Moyen, Mauvais, Déclassé)

state_badge(string $state): string
// Retourne le HTML du badge complet prêt à afficher
```

### 4. Routes et Contrôleur (EquipementController)

#### Nouvelle Méthode: changeState()
```
POST /equipements/{id}/change-state
Paramètres POST:
- equipement_id (int)
- nouvel_etat (string: neuf|bon|moyen|mauvais|declasse)
- commentaire (string, obligatoire)

Authentification: Agent IT requis
Réponse: Redirection avec message de succès/erreur
```

#### Nouvelle Méthode: getStateHistory()
```
GET /equipements/{id}/state-history
Paramètres GET:
- id (int)

Retour: JSON avec array d'historique
```

#### Modification de la Méthode: show()
- Charge automatiquement `state_history` pour affichage dans la vue

### 5. Vues Créées

#### Modals: views/equipements/modals/change_state.php
- Formulaire modale pour changer l'état
- Dropdown avec les 5 états possibles
- Textarea mandatory pour commentaire
- Bouton de confirmation

#### Partiels: views/equipements/partials/

**state_display.php**
- Affiche l'état réel ET l'état théorique en badges colorés
- Bouton "Modifier l'état" qui ouvre la modal

**state_history.php**
- Timeline des changements d'état
- Chaque entrée montre:
  - Date/heure du changement
  - Ancien état → Nouvel état
  - Agent qui a effectué
  - Commentaire
- Ordonnées par date DESC (plus récent d'abord)

**state_management_integration.txt**
- Instructions pour intégrer dans show.php

## États et Transitions

### États Valides
1. **neuf**: Équipement neuf, jamais utilisé
2. **bon**: Équipement fonctionnel en bon état
3. **moyen**: État intermédiaire, dégradation visible
4. **mauvais**: Défaillant mais réparable
5. **declasse**: Hors service, non attribuable

### États Théoriques (automatiques)
- Même énumération mais avec 'a_declasser' au lieu de 'declasse'
- Calculé à partir de l'âge de l'équipement
- Mise à jour via commande batch ou au besoin

### Transition des États Réels
- **Libre**: Tout agent IT peut passer d'un état à n'importe quel autre
- **Obligatoire**: Le commentaire doit toujours être fourni
- **Immédiate**: Le changement est enregistré immédiatement avec timestamp

## Audit et Conformité

### Traçabilité Complète
- Qui a changé l'état? → `agent_username`
- Quand? → `created_at` (timestamp)
- Pourquoi? → `commentaire` (obligatoire)
- Avant/Après? → `ancien_etat`, `nouvel_etat`

### Historique Permanent
- Tous les changements sont conservés dans les tables d'historique
- Aucune suppression/modification d'historique permise
- Utilisation par le reporting et conformité AS-IS

## Utilisation Typique

### Workflow Quotidien
1. **Réception équipement** → État théorique auto calculé, état réel = neuf
2. **Dégradation observée** → Agent IT met à jour l'état réel (+ commentaire)
3. **Consultation** → Historique visible dans fiche équipement
4. **Reporting** → Filtrage par état réel/théorique pour analyse

### Commandes Batch
```php
// Via CLI ou task scheduler:
$equipement = new Equipement();
$count = $equipement->updateAllTheoreticalStates();
// Recalcule les états théoriques pour tous les actifs non-déclassés
```

## Integration avec Phase 1 (Dates/Âge)

### Dépendance
- L'état théorique est calculé à partir de l'âge (Phase 1)
- Si pas de données de date → état théorique par défaut = 'bon'
- L'âge utilise la priorité: mise_service > achat > année_estimee

### Fiabilité
- État théorique reflète uniquement l'âge
- État réel reflète l'observation/test de l'agent
- La différence (théorique vs réel) est un indicateur de fiabilité d'équipement

## Déploiement

### Migration BD
```bash
cd database/migrations
mysql -u root itam_db < 2026-05-11-etat-refactor.sql
```

### Fichiers à Deployer
- `models/Equipement.php` (✅ updated)
- `models/Stock.php` (✅ updated)
- `config/helpers.php` (✅ updated)
- `controllers/EquipementController.php` (✅ updated)
- `views/equipements/modals/change_state.php` (✅ created)
- `views/equipements/partials/state_display.php` (✅ created)
- `views/equipements/partials/state_history.php` (✅ created)

### Intégration Vue show.php
À ajouter avant `</div>` de la colonne droite:
```php
<?php include __DIR__ . '/partials/state_display.php'; ?>
<?php include __DIR__ . '/partials/state_history.php'; ?>

<?php // At the very end before scripts: ?>
<?php include __DIR__ . '/modals/change_state.php'; ?>
```

## État Actuel

### ✅ Terminé
- [x] Schéma DB avec nouvelle colonne etat_theorique
- [x] Tables d'historique créées
- [x] Migration exécutée et vérifiée
- [x] Modèles Equipement et Stock avec méthodes
- [x] Helpers pour affichage des états
- [x] Routes et contrôleur
- [x] Vues partielles créées
- [x] Modal de changement d'état

### ⚠️ En Cours / À Finaliser
- [ ] Intégration des vues partielles dans show.php (prise en compte des includes)
- [ ] Tests end-to-end (création, modification, consultation historique)
- [ ] CSS timeline si nécessaire (utilise Bootstrap par défaut)
- [ ] Documentation utilisateur

## Tests Recommandés

1. **Test Création État**
   - Créer equipement → vérifier état_theorique auto-calculé
   - Changer état → vérifier historique enregistré

2. **Test Modal**
   - Ouvrir modal → remplir formulaire → soumettre
   - Vérifier redirection et message flash

3. **Test Historique**
   - Plusieurs changements d'état
   - Vérifier order DESC, timestamps, commentaires

4. **Test BD**
   - Vérifier colonnes exist
   - Vérifier triggers/constraints

## Notes de Performance

- **updateAllTheoreticalStates()**: O(n) - peut être lourd sur 10k+ items
  - Envisager batch traitement ou cron job
- **getEtatHistorique()**: Indexed on `equipement_id`, rapide
- **changeEtat()**: Transaction atomique, sûr

## Prochaines Étapes Possibles

1. Ajouter filtrage par état_theorique dans liste équipements
2. Dashboard avec statistiques état théorique vs réel
3. Règles d'alerte si écart état théorique/réel > seuil
4. Export historique pour audit/conformité
5. Webhook vers suivi des actifs
