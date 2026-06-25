# ITAM - IT Asset Management (PHP Natif + MySQL)

Application intranet de gestion des actifs informatiques pour une banque.

## Fonctionnalites couvertes

- Authentification securisee (session + roles)
- Dashboard ITAM (equipements, categories, stock, utilisateurs)
- Gestion des categories dynamiques et attributs
- Gestion des equipements individuels
- Gestion du stock par quantite et par etat
- Attribution automatique utilisateur â†” stock
- Gestion des utilisateurs/employes (CRUD + recherche)
- TraÃ§abilite via mouvements automatiques
- Gestion des demandes (creation, validation/refus, impression)
- Historique par equipement et par stock
- Recherche avancee des equipements (serial, hostname, utilisateur, type)
- Reporting (par site, par departement)
- Export CSV (site et departement)

## Stack

- PHP natif (MVC)
- MySQL
- HTML/CSS/JS + Bootstrap

## Arborescence

- `config/` : noyau app (router, db, auth, helpers)
- `controllers/` : logique HTTP
- `models/` : acces donnees
- `views/` : templates
- `public/` : point d'entree web
- `assets/` : CSS/JS
- `routes/` : declaration des routes
- `database/` : scripts SQL

## Installation (XAMPP)

1. Placer le projet dans `c:\xampp\htdocs\ITAM`.
2. Creer la base avec le script:
   - Ouvrir phpMyAdmin
   - Importer `database/schema.sql`
3. Verifier la config de connexion dans `config/config.php`:
   - DB par defaut: `itam_db`
   - User: `root`
   - Password: vide
4. Demarrer Apache et MySQL dans XAMPP.
5. Ouvrir l'application:
   - `http://localhost/ITAM/public`

Compte initial IT:
- Username: `admin`
- Password: `Admin@123`

Compte special depot:
- Nom: `STOCK_IT`
- Matricule: `STOCK_IT`

## Architecture BDD V2

Le projet contient maintenant une base cible V2 inspiree du nouveau modele IT Asset Management:

- Schema complet pour la base active `itam_db`: `database/schema_v2.sql`
- Ancienne migration de transition gardee comme reference: `database/migrations/2026-06-23-v2-foundation.sql`
- Analyse d'integration: `docs/ARCHITECTURE_BDD_V2_ANALYSE.md`

Importer `schema_v2.sql` recree une architecture V2 propre dans `itam_db`. Les utilisateurs se connectent avec leur PF/matricule, leur email ou leur username. Les mots de passe generes doivent etre changes a la premiere connexion.

## Migration base existante (sans reset)

Si tu as deja une base `itam_db` en production locale et que tu ne veux pas la reinitialiser:

1. Importer la migration suivante dans phpMyAdmin:
   - `database/migrations/2026-04-04-utilisateurs-upgrade.sql`
2. Importer aussi la migration attributs:
   - `database/migrations/2026-05-03-attributs-required.sql`
3. Importer la migration dates (refactoring gestion des dates):
   - `database/migrations/2026-05-10-dates-refactor.sql`
4. Ces migrations ajoutent:
   - `utilisateur_accessoires`
   - `utilisateur_audits`
   - index unique `uk_utilisateurs_matricule` si aucun doublon de matricule n'est detecte
   - colonne `required` sur `attributs`
   - colonnes `date_achat`, `date_mise_service`, `date_fiabilite`, `annee_estimee` sur `equipements` et `stocks`

Commande alternative en terminal Windows (XAMPP):

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root itam_db < "database\migrations\2026-04-04-utilisateurs-upgrade.sql"
```

## Gestion des dates et de l'age des equipements (v2)

Le systeme a ete refonde pour supporter les donnees temporelles incompletes des anciens equipements:

### Changements DB
- **Nouvelle colonne** `date_enregistrement` : quand l'equipement a ete entre dans le systeme (auto)
- **Nouvelle colonne** `date_achat` : date exacte d'achat (optionnel)
- **Nouvelle colonne** `date_mise_service` : date de deploiement reel (optionnel)
- **Nouvelle colonne** `date_fiabilite` : niveau de confiance (`exacte`, `approximative`, `inconnue`)
- **Nouvelle colonne** `annee_estimee` : annee estimee pour les anciennes donnees (optionnel)
- **SUPPRIMÃ‰E** l'ancienne colonne `date_ajout` (remplacÃ©e par `date_enregistrement`)

### Logique de calcul d'age
1. Les dates NE SONT PAS obligatoires
2. L'age est calcule dans cet ordre de priorite:
   - `date_mise_service` (utilisation reale)
   - `date_achat` (si pas de service)
   - `annee_estimee` (si dates exactes inconnues)
   - `null` (pas de donnees)
3. L'age n'affecte PAS automatiquement l'etat reel de l'equipement
4. L'age sert uniquement pour:
   - Statistics et reporting
   - Alertes de renouvellement (optionnel)
   - Audits

### Fiabilite des donnees
Chaque equipement indique la qualite de l'info temporelle:
- `exacte` : date exacte connue â†’ affichage vert
- `approximative` : estimation â†’ affichage jaune
- `inconnue` : aucune info â†’ affichage gris

### Interface utilisateur
- Creation/edit d'equipement: section "Informations temporelles" (optionnel)
- Fiche equipement: affichage de l'age calcule avec source et fiabilite
- Import CSV: supporte les colonnes de dates (optionnel)

### Avantages
âœ… Accepte equipements anciens sans donnees completes
âœ… Fonctionne avec parc existant
âœ… Separation claire entre age theorique et etat reel
âœ… Flexibilite pour donnees partielles ou approx

## Compte initial

- Username: `admin`
- Password: `Admin@123`
- Role: `Admin`

## Notes securite

- Mots de passe hashes avec bcrypt (`password_hash`/`password_verify`)
- Requetes preparees PDO (anti SQL injection)
- Echappement HTML centralise via `e()` (anti XSS)
- Token CSRF sur les formulaires sensibles
- Controle d'acces par role (`Auth::requireRole`)

## Bonus deja inclus

- Validation formulaires cote client (Bootstrap)
- Validation cote serveur
- Export CSV
- Impression fiche demande

## Evolutions recommandees

- Pagination SQL sur les grandes listes
- Journal d'audit complet (qui a fait quoi)
- Piece jointe sur demande
- Generation PDF (Dompdf)
- Politique mot de passe forte + expiration session
- Verrouillage apres tentatives de login echees


