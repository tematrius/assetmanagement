---
name: itam-code
description: Développer une fonctionnalité ou corriger un bug dans le MVC PHP natif ITAM.
---

Lire CLAUDE.md puis les routes, contrôleurs, modèles et vues du parcours concerné.
Vérifier les usages existants avant de modifier les signatures, permissions ou requêtes.
Préserver les règles V2, les attributs dynamiques, les dates facultatives et la traçabilité.
Pour une opération composée (affectation, retour, livraison), examiner transactions, rollback,
concurrence et invariants de quantité avant de choisir la correction.
Réutiliser Auth, les helpers CSRF, e() et base_url(). Examiner les appels voisins.
Préparer une migration SQL explicite si nécessaire ; ne pas l'appliquer implicitement.
Valider chaque fichier PHP modifié avec le php.exe XAMPP en mode -l.
Livrer un résumé des changements, preuves de vérification et risques restants.

