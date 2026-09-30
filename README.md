# amon/laravel-module-generator

Outil de développement (`require-dev`) : génère des modules CRUD Laravel + Inertia + React (JSX) sous forme de vrais fichiers,
qui n'ont aucune dépendance au package une fois générés.

> Phase 1 (squelette) : le package ne génère encore rien.

## Développement

```bash
composer install
vendor/bin/pest
vendor/bin/pint --test
vendor/bin/phpstan analyse
```
