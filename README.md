# amon/laravel-module-generator

Générateur de modules CRUD pour **Laravel 12 / 13 + Inertia + React (JSX) + Tailwind**.

C'est un **outil de développement** (`require-dev`) : il génère de vrais fichiers qui appartiennent à votre projet,
puis s'efface. Le code généré ne dépend **jamais** du package : il fonctionne en production (`composer install --no-dev`).

- Un module = modèle, migration, enum(s), contrôleur, requests, filtre, factory, routes, entrée de menu et pages React.
- Un module ne modifie **aucun fichier partagé** : il possède tous ses fichiers, listés dans un manifest versionné.
- Pas de mise à jour de module : pour changer, on **supprime** puis on **régénère** (depuis la définition archivée).
- CLI et interface web utilisent exactement le même moteur.

## Prérequis

- PHP 8.2+ (Laravel 13 : PHP 8.3+), Laravel 12 ou 13
- Inertia (`inertiajs/inertia-laravel`) + React en JSX, Tailwind CSS, Ziggy (`tightenco/ziggy`, directive `@routes`)
- npm : `react`, `react-dom`, `@inertiajs/react`, `lucide-react`
- Structure en arbre : `kalnoy/nestedset` (`^6.0.7` pour Laravel 12, `^7.0` pour Laravel 13)

## Installation

```bash
composer require --dev amon/laravel-module-generator
php artisan module-generator:install
```

L'installation (une seule fois) :

- publie l'échafaudage qui **appartient à votre projet** (jamais écrasé s'il existe déjà) :
  kit UI JSX (`resources/js/components/admin`), `app/Filters/QueryFilter.php`, `app/Support/Admin/MenuRegistry.php`,
  `routes/admin-modules.php`, `config/admin/menu-groups.php` ;
- après confirmation, ajoute **une ligne** à `routes/web.php` et **une prop partagée** `admin` à `HandleInertiaRequests`
  (avec marqueur `module-generator`) ;
- vérifie le résolveur de pages Inertia (`.jsx`), `@routes` et les dépendances, et affiche les commandes à lancer
  sans rien installer.

Options : `--dry-run`, `--yes` (accepte les deux modifications sans question).

## Générer un module

```bash
php artisan module-generator:make Invoice \
    --field=number:string:unique \
    --field="status:enum(draft,sent,paid):default=draft" \
    --field="amount:decimal(12,2)" \
    --field=issued_at:date \
    --field=attachment:file:nullable \
    --relation=belongsTo:Client \
    --relation=belongsToMany:Tag:pivot=generate:form \
    --icon=file-text

php artisan migrate
npm run build
```

Sans champ, la commande pose les questions (Laravel Prompts). `--dry-run` affiche le plan réel sans rien écrire
(`--show-content` pour le code, `--format=json` pour le plan complet).

### Champs : `--field=nom:type[(options)][:modificateurs]`

Types : `string, text, integer, bigInteger, decimal, boolean, date, datetime, time, email, password, enum, json,
foreignId, uuid, file, image`.

| Exemple | Effet |
|---|---|
| `title:string(100)` | longueur maximale |
| `price:decimal(12,2)` | précision, décimales |
| `status:enum(draft,sent)` | classe d'enum PHP propre au module, avec libellés |
| `stock:integer(0,500)` | min, max |
| `user_id:foreignId(users,cascade)` | table référencée, comportement à la suppression |
| `cv:file(pdf,docx)` / `photo:image` | extensions acceptées |

Modificateurs : `nullable unique index searchable sortable filterable`, `hidden` (hors tableau), `nodetail`, `noform`,
`default=…`, `label=…`.

### Relations : `--relation=type:Cible[:options]`

- `belongsTo:Client[:nom][:nullable][:display=colonne]` : méthode, colonne `client_id` contrainte, select du formulaire.
- `hasOne:Receipt`, `hasMany:InvoiceLine:lines` : méthode seule.
- `belongsToMany:Tag[:pivot=none|generate|module=<slug>][:timestamps][:form]` : sans table, table pivot générée par
  le module, ou module pivot existant (`using()`). `form` ajoute une sélection multiple (`sync()`), disponible si le
  pivot n'a pas de champs supplémentaires.

Les relations sont **déclaratives** : rien n'est vérifié ni injecté dans les modèles cibles.

### Structures

```bash
# Arbre (Nested Set) : liste indentée, parent différent de soi-même et de ses descendants
php artisan module-generator:make Category --field=name:string --tree

# Polymorphe : select du type puis de l'élément ; colonne affichée par type (name par défaut)
php artisan module-generator:make Comment --field=body:text --morph=commentable --morph-types=Client,Invoice:number

# Module pivot : vrai module reliant deux modèles, unicité du couple
php artisan module-generator:make "Client tag" --pivot-of=Client,Tag --field=note:string:nullable
```

Autres options : `--model --table --slug --plural --icon --group --order --permission --no-menu --per-page
--sort=champ[:desc] --soft-deletes --no-timestamps --no-id --no-unique-pair --morph-key=int|uuid|ulid
--morph-nullable --json=<fichier> --from=<archive>`.

## Supprimer et régénérer

```bash
php artisan module-generator:remove invoices                     # fichiers modifiés conservés
php artisan module-generator:remove invoices --include-modified  # avec confirmation (--force pour les scripts)
php artisan module-generator:make --from=invoices                # régénère depuis la définition archivée
php artisan module-generator:list [--archived]
```

Supprimer un module = supprimer les fichiers de son manifest, rien d'autre : la table reste en base (lancer
`migrate:rollback` avant si nécessaire) et aucune référence n'est recherchée. Les fichiers modifiés depuis la
génération ne sont supprimés que sur demande explicite ; les dossiers vides créés par le module sont retirés.

## Projet basé sur le starter kit officiel (TypeScript)

Le starter kit React de Laravel est en TypeScript ; les modules générés sont en JSX et cohabitent avec lui :

1. `config/module-generator.php` : `'pages_path' => 'resources/js/pages'` (nommage Breeze conservé dans le dossier du kit),
   ou `'convention' => 'starter-kit'` (nommage en minuscules) ;
2. avec le plugin `@inertiajs/vite` (Inertia v3), les `.jsx` sont déjà résolus ; sinon, faire accepter les `.jsx` au
   résolveur de `resources/js/app.tsx` (l'installation affiche le code à utiliser) ;
   si `app.tsx` définit un callback `layout`, retourner `null` pour les pages générées (`case /^[A-Z]/.test(name):`),
   qui ont déjà leur `AdminLayout` ;
3. ajouter `@routes` (Ziggy) dans `resources/views/app.blade.php`.

## Interface web

En environnement `local`, l'outil est disponible sur `/module-generator` : liste des modules, constructeur avec options
dynamiques par type, prévisualisation du code, génération et suppression protégée. Précompilée, elle ne dépend pas du
Vite de votre projet. Réglages : `module-generator.ui` (`enabled`, `prefix`, `middleware`).

## Configuration

`php artisan vendor:publish --tag=module-generator-config`

| Clé | Défaut | Rôle |
|---|---|---|
| `convention` | `breeze` | `resources/js/Pages/Invoices/Index.jsx` ; `starter-kit` : `resources/js/pages/invoices/index.jsx` |
| `pages_path` | `null` | dossier racine des pages (sinon celui de la convention), ex. `resources/js/pages` |
| `naming.french_plural` | `false` | pluriel français des tables et slugs (sinon pluriel anglais de Laravel) |
| `naming.models_namespace` | `App\Models` | espace de noms des modèles |
| `format.pint` / `format.prettier` | `true` / `false` | formatage des fichiers générés (empreintes calculées après) |
| `field_types` | `[]` | types de champs supplémentaires (classes implémentant `FieldType`) |
| `stubs_path` | `stubs/module-generator` | stubs du projet, prioritaires sur ceux du package |
| `manifest_path` | `.module-generator` | manifests (à versionner) et archives |

`php artisan module-generator:publish-stubs` publie les stubs pour les personnaliser. Ils utilisent des placeholders
`%%NOM%%` (pas de Blade, pour ne pas entrer en collision avec les accolades JSX).

## Manifest

`.module-generator/modules/<slug>.json` : identité, statut (`pending` / `complete`), versions, date, définition
normalisée complète, fichiers (chemin, nature, sha256 normalisé LF sans BOM) et dossiers créés. À versionner dans git.

## Autorisations

v1 : middleware `auth` sur toutes les routes d'administration ; `permission` masque l'entrée de menu (Gate).

## Développement

```bash
composer install && npm install
vendor/bin/pest                                               # unitaires, golden files, CRUD généré, architecture
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run build                                                 # interface de l'outil (dist/)
UPDATE_GOLDEN=1 vendor/bin/pest tests/Unit/GoldenTest.php     # après un changement voulu du code généré
.github/playground/run.sh                                     # Laravel frais : install, génération, vite build, ESLint
```

## Licence

MIT
