---
name: itam-delivery
description: Coordonner les agents ITAM pour développer, tester et revoir une demande.
---

Lire CLAUDE.md. Définir le périmètre et des critères d'acceptation observables pour : $ARGUMENTS
Lire docs/CLAUDE_AGENTS_GUIDE.md pour préparer les accès et comptes de test.
Vérifier le serveur Playwright et l'accès à l'application avant d'annoncer des tests visuels.
Attribuer le navigateur à un seul agent à la fois, même pour des audits en lecture.
Fermer la session entre agents ou rôles. Demander une capture, les erreurs console/réseau
pertinentes et les notes UX justifiées à itam-qa ; exiger les résultats réellement observés.
Les navigateurs isolés partagent toujours la base : identifier la base de test avant les écritures.
Pour un audit sans modification : confier les analyses ciblées à itam-qa et itam-security ;
utiliser itam-testeur uniquement si des vérifications exécutables sont nécessaires.
Pour une implémentation :
1. Confier la réalisation à itam-developpeur avec exigences, fichiers concernés et contraintes.
2. Sur le résultat stabilisé, confier les tests à itam-testeur et la revue à itam-qa.
3. Confier à itam-security la revue des changements sensibles (droits, données, imports,
   exports, sessions, SQL) ; pour un audit complet, lui transmettre le périmètre complet.
4. Transmettre les constats confirmés au développeur puis revérifier les points corrigés.
Ne pas laisser plusieurs agents modifier le même fichier ou partager une base de test en écriture
en parallèle. Transmettre les rapports entre étapes : les agents ne connaissent pas tout le chat.
Ne pas lancer quatre audits complets pour une simple correction de texte.
Après deux cycles avec le même blocage, expliquer la cause et les éléments manquants.
Terminer par exigences couvertes, tests PASS/FAIL/NON EXÉCUTÉ, anomalies restantes et
verdict limité aux preuves. Aucun déploiement, commit ou changement destructif implicite.

