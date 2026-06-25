# Architecture BDD V2 - Analyse d'integration

Ce document resume l'ecart entre le projet actuel et le PDF "Nouvelle Architecture Bdd It Asset Management (2).pdf".

## Ce que le projet actuel couvre deja

- Authentification des agents IT via `users_system` et `roles`.
- Employes/inventaire via `utilisateurs`.
- Categories dynamiques via `categories`, `attributs`, `categorie_attribut_options`.
- Equipements uniques via `equipements` et `valeurs_attributs`.
- Stock par quantite via `stocks`, `stock_etats`, `attributions_quantite`.
- Tracabilite via `mouvements` et historique d'etat ajoute par migration.
- Demandes simples avec fiche imprimable, signature et validation IT.
- Reporting par site/departement et exports.

## Ce que la V2 ajoute ou clarifie

- Un seul modele utilisateur cible pour comptes systeme + employes demandeurs.
- Roles systeme: `admin`, `agent_it`, `utilisateur_standard`.
- Fonctions metier: Directeur, Chef de departement, Chef de service, Employe.
- Validateurs autorises par utilisateur pour gerer la hierarchie.
- Workflow de demandes a deux niveaux: responsable puis manager IT.
- Table `validations_demandes` pour garder chaque decision.
- Table `notifications` pour alerter les acteurs.
- Table `attributions` unifiee pour equipement unique et stock quantitatif.
- Table `historique_equipements` orientee audit metier complet.

## Choix d'integration retenu

La base active `itam_db` a ete basculee en architecture V2 propre. Les anciennes tables applicatives V1 ne sont plus conservees dans le schema actif.

- `database/schema_v2.sql`: schema complet V2 pour la base active `itam_db`.
- `database/migrations/2026-06-23-v2-foundation.sql`: ancienne migration de transition, gardee comme reference historique, mais le projet part maintenant sur le schema clean.

Les modules applicatifs doivent maintenant etre migres progressivement vers les tables V2. L'authentification et le dashboard ont deja recu une adaptation minimale.

## Mapping entre V1 et V2

| V2 PDF | Projet actuel / strategie |
| --- | --- |
| `roles_systeme` | Remplace `roles`. |
| `fonctions_metier` | Nouveau. Sert a savoir qui peut valider. |
| `utilisateurs` | Table centrale pour comptes systeme et employes. |
| `validateurs_autorises` | Nouveau. Base du workflow hierarchique. |
| `categories_equipements` | Remplace `categories`. |
| `caracteristiques_categories` | Remplace `attributs`. |
| `options_listes` | Remplace `categorie_attribut_options`. |
| `equipements` | Table V2 inventaire unique. |
| `stocks_quantitatifs` | Remplace `stocks`/`stock_etats` pour le stock par quantite. |
| `attributions` | Unifie les attributions d'equipements uniques et quantitatifs. |
| `historique_equipements` | Nouveau. Audit metier complet. |
| `demandes` | Table actuelle enrichie sans casser les statuts existants. |
| `validations_demandes` | Nouveau. Journal des validations responsable/IT. |
| `notifications` | Nouveau. Notifications applicatives. |

## Ordre de developpement recommande

1. Stabiliser les utilisateurs: compte de connexion, profil employe, fonction metier, validateur autorise.
2. Adapter l'authentification pour accepter les roles systeme V2.
3. Refaire le workflow demandes: soumission utilisateur, validation responsable, validation IT, attribution.
4. Refondre les attributions pour utiliser une table commune.
5. Brancher `historique_equipements` sur chaque operation d'inventaire.
6. Ajouter les notifications.
7. Migrer le reporting vers les nouvelles tables.
