---
name: itam-ux-accessibility
description: Évaluer l'ergonomie, l'accessibilité et la qualité visuelle des parcours ITAM.
---

Reconstituer l'objectif de l'utilisateur et le parcours par rôle avant d'évaluer l'écran.
Consulter docs/INTERFACES_PAR_PROFIL.md si pertinent et le confronter au code.

## Points d'observation
- Navigation : repères, titre de page, action principale identifiable, retour au contexte.
- Formulaires : labels associés, champs requis compréhensibles, instructions utiles,
  erreurs liées aux champs, saisies conservées et confirmation explicite.
- Listes : filtres compréhensibles, résultat vide utile, pagination, recherche,
  colonnes lisibles, nombres et dates cohérents.
- Actions sensibles : effet compréhensible avant validation, résultat et erreurs visibles.
- Accessibilité : parcours au clavier, ordre et visibilité du focus, absence de piège,
  noms accessibles, structure des titres, labels, dialogues et annonces d'état.
- Vérifier le zoom à 200 %, le reflow, les cibles interactives et le contenu long.
- Ne pas dépendre uniquement de la couleur pour signaler un état.
- Pour un contraste mesuré, viser WCAG 2.2 AA : 4,5:1 pour texte courant, 3:1
  pour grand texte et composants concernés. Consulter le standard officiel si besoin.
- Sans mesure ou outil approprié, signaler le contraste comme à vérifier.
- Une vérification automatisée ne prouve pas une conformité WCAG complète.

## Évaluation
Pour chaque dimension (navigation, formulaires, lisibilité, responsive, accessibilité,
retour utilisateur), donner une appréciation de 1 à 5 appuyée par des observations.
1 = obstacle bloquant, 2 = difficultés majeures, 3 = utilisable avec défauts,
4 = défauts mineurs, 5 = aucun défaut observé sur les scénarios testés.
Utiliser N/V si non vérifié. Une note est une appréciation, pas une certification.
Séparer les bugs des propositions d'amélioration et prioriser selon fréquence, impact,
nombre de rôles touchés et risque d'erreur métier. Proposer une correction concrète.
