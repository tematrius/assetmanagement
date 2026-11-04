# Instructions pour travailler sur ITAM

## Projet et communication

- ITAM est une application intranet de gestion des actifs informatiques pour une banque.
- Répondre en français et expliquer simplement les changements.
- Conserver les libellés de l'interface en français et l'encodage UTF-8.
- Pour chaque demande, lire le parcours concerné avant de modifier les fichiers.
- Réaliser une modification ciblée ; éviter les refontes sans rapport avec la demande.
- Si une règle métier est ambiguë, examiner le code et la documentation puis demander une précision si nécessaire.

## Environnement et architecture

- Windows, PowerShell et XAMPP ; dossier local : `C:\xampp\htdocs\ITAM`.
- URL locale documentée : `http://localhost/ITAM/public` (Apache et MySQL démarrés).
- PHP natif avec MVC maison, MySQL et PDO, HTML/CSS/JavaScript et Bootstrap.
- Composer gère les dépendances PHP ; `composer.json` déclare Dompdf.
- Ne pas introduire un framework ou une nouvelle dépendance sans besoin explicite.
- `public/index.php` : point d'entrée web ; `config/bootstrap.php` : initialisation.
- `routes/web.php` : routes et méthodes HTTP.
- `controllers/` : traitement HTTP, validation des entrées et contrôle d'accès.
- `models/` : accès aux données et logique métier existante.
- `views/` : vues PHP ; `views/layouts/app.php` : structure commune.
- `assets/` : ressources CSS et JavaScript.
- `config/` : connexion PDO, authentification, routeur et fonctions communes.
- `database/migrations/` : évolutions SQL explicites.
- `docs/` : documentation métier et technique à consulter selon la tâche.
- `tools/` : scripts de diagnostic ou de maintenance ; lire leur code avant exécution.

## Conventions à conserver

- Suivre le style des fichiers voisins et leurs conventions de typage PHP.
- Déclarer les routes dans le routeur existant et utiliser `base_url()` pour les liens.
- Réutiliser les modèles et fonctions existants avant d'ajouter une abstraction.
- Utiliser des requêtes préparées PDO pour les valeurs provenant des entrées.
- Échapper les données affichées dans le HTML avec `e()` selon le contexte.
- Protéger les actions qui modifient les données avec la validation CSRF existante.
- Contrôler les autorisations côté serveur avec les mécanismes existants de `Auth`.
- Ne pas déduire les permissions du seul affichage ou masquage d'un bouton.
- Conserver le hachage des mots de passe et la gestion sécurisée des sessions.

## Règles métier et données

- Préserver la cohérence entre équipements, stocks, affectations et mouvements.
- Conserver la traçabilité des actions et les contrôles du workflow des demandes.
- Examiner `config/Auth.php` avant de modifier les permissions : rôle système,
  fonction métier et capacité de validation sont des notions distinctes.
- L'âge d'un équipement ne doit pas changer automatiquement son état réel.
- Respecter les dates facultatives et les niveaux de fiabilité des dates.
- La référence de schéma V2 est `database/schema_v2.sql` ; le schéma réel
  peut aussi inclure des migrations ultérieures. Vérifier les colonnes utilisées.
- Le README contient aussi des informations historiques V1 ; les confronter au code actuel.
- Ne pas réintroduire de création ou migration implicite de tables au démarrage
  dans `config/ensure_migration.php`.
- Pour une évolution de schéma, préparer un nouveau fichier SQL daté dans
  `database/migrations/`, expliquer son effet et préserver les données existantes.
- Ne pas réimporter un schéma complet, réinitialiser la base ou exécuter une
  migration sans demande explicite d'application de ces changements.
- Ne pas exposer de secrets ou de données personnelles dans les réponses ou les logs.
- Éviter de lire les sauvegardes SQL et données de `storage/` sans nécessité.

## Méthode de travail et vérification

1. Examiner les modifications locales existantes et ne pas les écraser.
2. Suivre le parcours routes → contrôleur → modèle → vue pour la tâche concernée.
3. Pour une tâche complexe, expliquer brièvement l'approche avant de modifier.
4. Implémenter le changement en conservant les comportements voisins.
5. Vérifier la syntaxe de chaque fichier PHP modifié avec, par exemple :

   ```powershell
   & 'C:\xampp\php\php.exe' -l '.\controllers\EquipementController.php'
   ```

6. Vérifier le parcours concerné dans l'application si un accès est disponible :
   succès, entrée invalide et accès refusé selon les rôles pertinents.
7. Pour une modification métier, tester aussi la cohérence des données sur une
   base de test et les effets sur les mouvements ou demandes concernés.
8. Ne pas considérer les scripts de `tools/` comme une suite de tests garantie.
9. Terminer par les fichiers modifiés, le résultat, les vérifications réellement
   effectuées et les éventuelles étapes manuelles restantes.

Ne pas prétendre qu'un test ou une vérification visuelle a été effectué sans l'avoir fait.
Ne pas modifier directement `vendor/` ni les sauvegardes de `database/backups/`.
Ne pas créer de commit, pousser ou déployer sans demande de l'utilisateur.
