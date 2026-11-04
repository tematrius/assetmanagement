---
name: itam-tests
description: Concevoir et exécuter les tests de régression des parcours PHP MySQL ITAM.
---

Identifier d'abord les outils de test réellement présents ; ne pas supposer PHPUnit installé.
Partir des critères d'acceptation et du comportement attendu, pas d'une copie de l'implémentation.
Couvrir le succès, les entrées invalides, l'absence d'authentification et les droits insuffisants.
Pour les données : affectation, transfert, retour, état, stock et historique doivent rester cohérents.
Pour les demandes : tester les transitions permises, interdites et la double soumission.
Tester les dates manquantes et attributs requis si le parcours les utilise.
Les tests qui écrivent en base nécessitent une base de test identifiée, des données synthétiques
et une stratégie de nettoyage. Sans cet environnement, proposer les tests et signaler le blocage.
Ne jamais réinitialiser la base locale existante ni supposer qu'elle est jetable.
Créer ou modifier les tests dans tests/ et les preuves dans .qa-artifacts/ ;
transmettre les corrections applicatives au développeur.
Distinguer PASS, FAIL et NON EXÉCUTÉ. Fournir commande, résultat attendu et observé.
Un lint PHP ne prouve ni le fonctionnement métier ni la qualité de l'interface.

