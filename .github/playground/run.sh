#!/usr/bin/env bash
# Crée une application Laravel 13 fraîche (Inertia + React JSX + Tailwind), installe le package,
# génère les modules de référence puis vérifie : migrations, vite build, ESLint.
# Usage : .github/playground/run.sh [dossier] ; SYMLINK=false si les liens symboliques sont indisponibles.
set -euo pipefail

PKG="$(cd "$(dirname "$0")/../.." && (pwd -W 2>/dev/null || pwd))"
APP="${1:-${RUNNER_TEMP:-/tmp}/mg-playground}"

rm -rf "$APP"
composer create-project laravel/laravel "$APP" "^13.0" --no-interaction --prefer-dist --quiet
cd "$APP"

composer config repositories.module-generator "{\"type\":\"path\",\"url\":\"$PKG\",\"options\":{\"symlink\":${SYMLINK:-true}}}"
composer require inertiajs/inertia-laravel tightenco/ziggy "kalnoy/nestedset:^7.0" --no-interaction --quiet
composer require --dev "amon/laravel-module-generator:@dev" --no-interaction --quiet

npm install --silent react react-dom @inertiajs/react lucide-react
npm install --silent -D @vitejs/plugin-react "eslint@^9" "@eslint/js@^9" eslint-plugin-react eslint-plugin-react-hooks globals

php artisan inertia:middleware --no-interaction
cp "$PKG/.github/playground/vite.config.js" "$PKG/.github/playground/eslint.config.js" .
cp "$PKG/.github/playground/app.jsx" resources/js/app.jsx
cp "$PKG/.github/playground/app.blade.php" resources/views/app.blade.php
php "$PKG/.github/playground/setup.php"

php artisan module-generator:install --yes --no-interaction

for definition in client invoice category pivot pair morphpivot; do
    php artisan module-generator:make --json="$PKG/tests/Fixtures/definitions/$definition.json" --no-interaction
done

php artisan migrate --force --no-interaction
php artisan route:list --path=admin > /dev/null
npm run build
npx eslint resources/js
echo "Playground OK : $APP"
