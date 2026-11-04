---
name: itam-security
description: Auditer défensivement la sécurité du code PHP MySQL et des accès ITAM.
---

Analyser les flux entrée → autorisation → requête → affichage/export.
Examiner authentification, sessions, changement de mot de passe et invalidation de session.
Contrôler les droits serveur et l'accès aux objets d'un autre utilisateur (IDOR),
en distinguant rôle système, fonction métier et capacité de validation.
Examiner CSRF, SQL injection (dont noms de colonnes dynamiques), XSS selon le contexte,
imports, fichiers, traversée de chemins, exports CSV et génération PDF.
Examiner l'exposition HTTP de config/, database/, storage/, tools/, vendor/ et sauvegardes,
ainsi que l'affichage d'erreurs et les secrets, sans reproduire leurs valeurs.
Vérifier les dépendances à partir de composer.lock ; un audit de vulnérabilités doit préciser
sa date et si les sources ont été consultées. Sans réseau, signaler cette limite.
Ne pas exécuter les scripts de maintenance pour voir ce qu'ils font.
Fournir pour chaque constat preuve fichier/ligne, préconditions, impact, gravité justifiée,
correction proposée et scénario de non-régression. Distinguer confirmé et à vérifier.
Audit statique par défaut : pas de scan actif, exploitation, écriture en base ou cible externe.
Une validation dynamique nécessite un périmètre explicitement autorisé et des données de test.
Ne pas modifier le code applicatif ; écrire les rapports dans .qa-artifacts/.
Ne pas promettre une certification de sécurité.

