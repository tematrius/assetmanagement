# Interfaces ITAM par profil

## Administrateur

- Tableau de bord global
- Equipements, stocks et mouvements
- Traitement des demandes et validation IT
- Categories et caracteristiques
- Creation, import, modification et suppression des utilisateurs
- Configuration des responsables validateurs
- Reporting et exports

## Agent IT

- Tableau de bord operationnel
- Equipements, stocks et mouvements
- Traitement des demandes apres validation du responsable
- Consultation des categories
- Consultation de l'annuaire utilisateurs
- Reporting et exports

L'agent IT ne peut pas creer ou modifier les comptes, les roles, les fonctions
metier ou les categories.

## Utilisateur standard

- Tableau de bord personnel
- Mon materiel
- Mes demandes
- Nouvelle demande
- Mon profil et mon circuit de validation

L'utilisateur ne voit jamais l'inventaire global, le stock global, le reporting
ou les fiches d'autres collaborateurs.

## Responsable validateur

Le responsable utilise le meme portail personnel que l'utilisateur standard et
dispose en plus de la page `Demandes a valider`.

Cette file contient uniquement les demandes des collaborateurs pour lesquels il
est declare dans `validateurs_autorises`.

## Securite

Les menus sont adaptes au profil, mais les autorisations sont aussi controlees
dans les controleurs. Une URL saisie manuellement renvoie HTTP 403 lorsque le
profil n'est pas autorise.
