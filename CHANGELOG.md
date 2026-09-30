# Changelog

## 1.1.1 (2026-09-30)

- Installation : le plugin `@inertiajs/vite` (Inertia v3) est reconnu, il résout déjà les pages `.jsx`.
- Installation : signale un callback `layout` global qui envelopperait les pages générées (double layout).

## 1.1.0 (2026-09-30)

- Option `pages_path` : dossier racine des pages, indépendant de la convention (ex. nommage Breeze dans
  `resources/js/pages` d'un projet basé sur le starter kit officiel).
- Imports du kit UI calculés selon l'emplacement réel des pages.
- Installation : résolveur mixte `.tsx` + `.jsx` proposé pour un point d'entrée TypeScript.
- Console : les commandes à lancer sont affichées sans ponctuation ajoutée.

## 1.0.0 (2026-09-30)

Première version.

- Définition de module (champs, relations, pivot, polymorphe, arbre), normalisation et validation.
- Moteur pur (plan), inspection des conflits, exécution atomique avec verrou et rollback, manifests.
- Générateurs PHP (migration, enum, modèle, requests, filtre, contrôleur, factory, routes, menu) et React JSX.
- Échafaudage publié chez l'hôte : kit UI, QueryFilter, MenuRegistry, chargeur de routes.
- Commandes `install`, `make`, `remove`, `list`, `publish-stubs` ; interface web locale.
- Compatible Laravel 12 et 13.
