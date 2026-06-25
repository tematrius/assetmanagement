# Audit application ITAM V2 - 24 juin 2026

## Perimetre verifie

- Authentification et changement obligatoire du mot de passe
- Utilisateurs et validateurs autorises
- Categories et caracteristiques dynamiques
- Equipements uniques, etats, attributions et historique
- Stocks quantitatifs, caracteristiques, attributions et retours
- Mouvements
- Demandes et double validation
- Dashboard et reporting
- Routes et vues principales

## Sources de verite V2

- `categories_equipements`, `caracteristiques_categories`, `options_listes`
- `equipements`, `valeurs_caracteristiques_equipements`
- `stocks_quantitatifs`, `valeurs_caracteristiques_stocks`
- `attributions`, `historique_equipements`
- `utilisateurs`, `roles_systeme`, `fonctions_metier`, `validateurs_autorises`
- `demandes`, `validations_demandes`, `notifications`

## Corrections appliquees

- Remplacement complet du modele de mouvements V1 par `historique_equipements`.
- Gestion transactionnelle des attributions, transferts, retours, maintenances et declassements.
- Remplacement du modele de demandes V1 par le workflow V2:
  `soumis -> validation_it -> approuve`, avec rejet possible.
- Controle du responsable autorise puis validation reservee a IT.
- Reconstruction du reporting sur les tables V2.
- Conversion de `TypeEquipement` en adaptateur vers les categories V2.
- Suppression de la creation automatique de tables V1 au demarrage.
- Migration de l'import CSV et des actions groupees d'equipements.
- Suppression des champs temporels V1 non persistants dans les formulaires.
- Ajout des routes et liens de navigation manquants.

## Verification

- Lint PHP passe sur 71 fichiers.
- Tests directs de lecture sur equipements, mouvements, demandes et reporting.
- Tests d'ecriture reussis: attribution, retour, demande, validation responsable et validation IT.
- Donnees temporaires supprimees apres les tests.
- Toutes les routes principales et les fiches disponibles repondent en HTTP 200.
- Aucun SQL actif ne reference les anciennes tables V1.

Les sauvegardes et anciennes migrations restent conservees comme historique et ne
sont pas chargees par l'application active.
