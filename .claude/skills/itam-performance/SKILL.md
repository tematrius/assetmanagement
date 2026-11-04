---
name: itam-performance
description: Diagnostiquer la performance des listes, tableaux de bord et exports PHP MySQL ITAM.
---

Établir une mesure de référence sur le même environnement, rôle et volume de données.
Séparer lenteur serveur, SQL, réseau et rendu. Répéter un petit nombre de mesures et noter
cache chaud/froid, navigateur et conditions ; ne pas attribuer un gain à une seule mesure.
Inspecter pagination SQL, requêtes N+1, filtres, tris, chargements inutiles et exports volumineux.
Examiner les plans SQL avec EXPLAIN si un accès adapté est disponible, sans ajouter d'index à l'aveugle.
Côté navigateur : examiner requêtes échouées ou répétées, ressources lourdes,
décalages de mise en page et temps de réaction selon les outils disponibles.
Utiliser Performance API seulement si un outil d'évaluation autorisé est disponible ;
ne pas déduire les Core Web Vitals de simples temps de navigation.
Ne pas lancer de test de charge ou benchmark intensif sur la base locale de travail.
Après correction, comparer sur les mêmes conditions et vérifier l'exactitude des résultats.
Livrer mesures, hypothèse, correction minimale et limites. Sans mesures, parler de risque potentiel.
