# Équipe Claude Code pour ITAM

## Outils préparés
Le fichier .mcp.json configure Playwright MCP 0.0.84 via Node/npx et Chrome.
Le navigateur est visible et utilise un profil temporaire, distinct du profil personnel.
Les agents ont accès au code, au terminal, aux recherches web et à Playwright selon
leur définition. Les autorisations de Claude Code restent applicables.
Les consignes limitant les écritures sont des règles de travail, pas une isolation système.
Le serveur de projet peut partager son navigateur : exécuter les campagnes une par une.

## Vérification de préparation
Les quatre définitions d'agents et neuf skills ont passé une vérification structurelle :
en-têtes, références des skills et déclaration des outils Playwright.
La commande `npx.cmd -y @playwright/mcp@0.0.84 --help` a réussi sur cette machine.
Cela confirme le téléchargement et le lancement du programme MCP, mais pas encore
la connexion de l'extension Claude Code ni une campagne dans un vrai navigateur.

## Activation dans VS Code
1. Ouvrir le dossier C:\xampp\htdocs\ITAM et relancer Claude Code.
2. Accepter le serveur Playwright de ce projet si Claude Code le demande.
   Cette confiance doit être accordée dans Claude Code ; elle ne peut pas être transférée depuis Codex.
3. Vérifier /mcp : playwright doit être connecté, puis /agents : quatre agents ITAM.
4. Démarrer Apache et MySQL depuis XAMPP si nécessaire.
5. Demander à itam-qa d'ouvrir http://localhost/ITAM/public, de lire la page de connexion
   et de prendre une capture, sans soumettre de formulaire. Cela valide le premier accès.
6. Si le navigateur ne démarre pas, relever l'erreur exacte avant de changer la configuration.
   Node et Chrome ont été trouvés sur cette machine ; l'extension doit aussi les voir dans son PATH.

## Campagne métier
Fournir URL, périmètre, critères d'acceptation, comptes de test pour les rôles nécessaires
et confirmation de la base de test avant toute opération métier en écriture.
Ne pas mettre les mots de passe dans ce document, CLAUDE.md ou un fichier versionné.
Le profil navigateur temporaire ne protège pas contre une modification de la base active.

Exemple :
« Utilise itam-qa pour examiner la navigation et les listes en lecture seule.
Évalue navigation, formulaires, responsive et accessibilité ; conserve les captures
dans .qa-artifacts/ et donne un rapport avec preuves. N'effectue aucune affectation
ni modification de demande. Signale les tests qui nécessitent un compte ou une base de test. »

Puis :
« /itam-delivery Corriger les anomalies confirmées de la campagne précédente,
dans l'ordre d'impact utilisateur, puis vérifier les parcours corrigés. »

## Rapport attendu
- Environnement, version/diff examiné, date, rôle et limites du périmètre.
- Tableau : ID, scénario, attendu, observé, PASS/FAIL/NON EXÉCUTÉ et preuve.
- Anomalies : gravité, étapes de reproduction, emplacement, impact et correction proposée.
- Notes UX par dimension sur 5, ou N/V ; justifier avec les observations.
- Risques sécurité, performance et intégrité des données séparés des préférences visuelles.
- Verdict : prêt sur le périmètre testé, corrections requises ou vérification incomplète.

Captures et rapports locaux vont dans .qa-artifacts/, ignoré par Git via son .gitignore.
Ne pas y stocker d'identifiants ou de dumps de données. Fermer les sessions après les campagnes.
Les skills fournissent des méthodes ; ils ne garantissent ni absence de bugs ni certification.
