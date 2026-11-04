---
name: itam-data-integrity
description: Vérifier les invariants MySQL et les migrations des équipements, stocks et demandes ITAM.
---

Lire le schéma V2, les migrations pertinentes et les modèles appelants.
Établir les invariants applicables à la tâche : quantité non négative, affectation cohérente,
état et localisation, unicité métier, historique et transitions autorisées des demandes.
Examiner transaction, annulation en cas d'échec, double soumission et accès concurrents.
Rechercher les validations effectuées seulement avant la transaction et devenues obsolètes.
Ne pas imposer un invariant nouveau sans preuve dans la règle métier.
Vérifier clés étrangères, index utiles et compatibilité de la migration avec les données existantes.
Préparer les migrations explicitement ; pas de DDL au bootstrap ni de reset de base.
Ne pas exécuter un outil de maintenance sans inspecter ses écritures et sa cible.
Tester les écritures sur une base de test identifiée avec fixtures synthétiques et nettoyage.
Un rollback n'annule pas nécessairement le DDL MySQL : ne pas le présenter comme protection.
Pour chaque test, comparer avant/après et vérifier aussi mouvements, notifications et demandes
lorsqu'ils sont touchés. Rapporter les contrôles non exécutés.
