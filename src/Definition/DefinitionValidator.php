<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Illuminate\Support\Str;

/**
 * Valide une définition normalisée. Retourne les erreurs par chemin (`fields.2.name`) ; tableau vide = valide.
 * Vérifications purement structurelles : l'existence des modèles cibles et les collisions avec les modules
 * déjà enregistrés relèvent du registre (phase 4) et de l'aperçu.
 */
final class DefinitionValidator
{
    private const SNAKE = '/^[a-z][a-z0-9_]*$/';

    private const MODEL = '/^[A-Z][A-Za-z0-9]*$/';

    private const METHOD = '/^[a-z][a-zA-Z0-9]*$/';

    private const CLASS_NAME = '/^[A-Z][A-Za-z0-9_]*(\\\\[A-Z][A-Za-z0-9_]*)*$/';

    private const ON_DELETE = ['cascade', 'restrict', 'null', 'no_action'];

    private const SYSTEM_COLUMNS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    private const TREE_COLUMNS = ['parent_id', '_lft', '_rgt'];

    private const TREE_METHODS = ['parent', 'children', 'ancestors', 'descendants', 'siblings'];

    /** Méthodes d'Eloquent qu'une relation ne doit pas masquer. */
    private const MODEL_METHODS = [
        'attributes', 'boot', 'create', 'delete', 'destroy', 'fill', 'find', 'fresh', 'load', 'newQuery',
        'push', 'query', 'refresh', 'relations', 'replicate', 'save', 'table', 'toArray', 'toJson', 'update',
    ];

    /** Mots réservés PHP interdits comme nom de classe. */
    private const PHP_RESERVED = [
        'abstract', 'and', 'array', 'as', 'bool', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const',
        'continue', 'declare', 'default', 'do', 'echo', 'else', 'empty', 'enum', 'eval', 'exit', 'extends', 'false',
        'final', 'float', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'int',
        'interface', 'isset', 'iterable', 'list', 'match', 'mixed', 'namespace', 'never', 'new', 'null', 'object',
        'or', 'parent', 'print', 'private', 'protected', 'public', 'readonly', 'require', 'return', 'self', 'static',
        'string', 'switch', 'throw', 'trait', 'true', 'try', 'unset', 'use', 'var', 'void', 'while', 'xor', 'yield',
    ];

    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @var array<string, string> colonne => propriétaire, pour détecter les doublons */
    private array $columns = [];

    /** @var array<string, string> méthode => propriétaire */
    private array $methods = [];

    public function __construct(private readonly FieldTypeRegistry $types) {}

    /**
     * @param  array<string, mixed>  $d  définition normalisée
     * @return array<string, list<string>>
     */
    public function validate(array $d): array
    {
        $this->errors = [];
        $this->columns = [];
        $this->methods = [];

        if (($d['schema_version'] ?? null) !== ModuleDefinition::SCHEMA_VERSION) {
            $this->add('schema_version', 'Version de schéma non prise en charge (attendu : '.ModuleDefinition::SCHEMA_VERSION.').');
        }

        $kind = ModuleKind::tryFrom(is_string($d['kind'] ?? null) ? $d['kind'] : '');

        if ($kind === null) {
            $this->add('kind', 'Nature attendue : standard ou pivot.');
        }

        $this->identity($d);
        $this->menu($d['menu'] ?? null);
        $this->bool($d, 'tree', 'tree');

        if ($kind === ModuleKind::Pivot && ($d['tree'] ?? false) === true) {
            $this->add('tree', 'Un module pivot ne peut pas être un arbre.');
        }

        if (($d['tree'] ?? false) === true) {
            foreach (self::TREE_COLUMNS as $column) {
                $this->columns[$column] = 'la structure en arbre';
            }

            foreach (self::TREE_METHODS as $method) {
                $this->methods[$method] = 'la structure en arbre';
            }
        }

        $this->pivotModule($d, $kind);
        $this->morph($d['morph'] ?? null);
        $this->fields($d['fields'] ?? null, $kind);
        $this->relations($d['relations'] ?? null, $d['table'] ?? null);
        $this->options($d['options'] ?? null, $d['fields'] ?? []);

        return $this->errors;
    }

    private function add(string $path, string $message): void
    {
        $this->errors[$path][] = $message;
    }

    /** @param  array<mixed>  $data */
    private function bool(array $data, string $key, string $path): void
    {
        if (! is_bool($data[$key] ?? null)) {
            $this->add($path, 'Un booléen est attendu.');
        }
    }

    private function text(mixed $value, string $path): void
    {
        if (! is_string($value) || trim($value) === '') {
            $this->add($path, 'Texte requis.');
        }
    }

    private function matches(mixed $value, string $pattern, string $path, string $message, int $max = 64): bool
    {
        if (! is_string($value) || ! preg_match($pattern, $value) || strlen($value) > $max) {
            $this->add($path, $message);

            return false;
        }

        return true;
    }

    private function snake(mixed $value, string $path): bool
    {
        return $this->matches($value, self::SNAKE, $path, 'Identifiant invalide : minuscules, chiffres et _, commence par une lettre (64 caractères max).');
    }

    private function model(mixed $value, string $path): bool
    {
        if (! $this->matches($value, self::MODEL, $path, 'Nom de modèle invalide : PascalCase, lettres et chiffres (ex. ClientContact).')) {
            return false;
        }

        if (in_array(strtolower($value), self::PHP_RESERVED, true)) {
            $this->add($path, "« {$value} » est un mot réservé de PHP.");

            return false;
        }

        return true;
    }

    /** Réserve une colonne de la table du module ; signale un doublon. */
    private function claimColumn(string $column, string $path, string $owner): void
    {
        if (isset($this->columns[$column])) {
            $this->add($path, "La colonne « {$column} » est déjà utilisée par {$this->columns[$column]}.");

            return;
        }

        $this->columns[$column] = $owner;
    }

    /** Réserve un nom de méthode du modèle ; signale un doublon. */
    private function claimMethod(string $method, string $path, string $owner): void
    {
        if (in_array($method, self::MODEL_METHODS, true)) {
            $this->add($path, "« {$method} » est une méthode d'Eloquent.");

            return;
        }

        if (isset($this->methods[$method])) {
            $this->add($path, "La méthode « {$method} » est déjà utilisée par {$this->methods[$method]}.");

            return;
        }

        $this->methods[$method] = $owner;
    }

    /** @param  array<string, mixed>  $d */
    private function identity(array $d): void
    {
        $this->text($d['name'] ?? null, 'name');
        $this->text($d['singular'] ?? null, 'singular');
        $this->model($d['model'] ?? null, 'model');
        $this->snake($d['table'] ?? null, 'table');
        $this->matches($d['slug'] ?? null, '/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', 'slug', 'Slug invalide : minuscules, chiffres et tirets (ex. client-contacts).');
        $this->matches($d['icon'] ?? null, '/^[a-z0-9]+(-[a-z0-9]+)*$/', 'icon', 'Icône invalide : minuscules, chiffres et tirets.');
    }

    private function menu(mixed $menu): void
    {
        if (! is_array($menu)) {
            $this->add('menu', 'Options de menu attendues.');

            return;
        }

        $this->bool($menu, 'enabled', 'menu.enabled');
        $this->matches($menu['group'] ?? null, '/^[a-z][a-z0-9_-]*$/', 'menu.group', 'Groupe invalide : minuscules, chiffres, _ et -.');

        if (! is_int($menu['order'] ?? null)) {
            $this->add('menu.order', 'Un entier est attendu.');
        }

        $permission = $menu['permission'] ?? null;

        if ($permission !== null && (! is_string($permission) || trim($permission) === '')) {
            $this->add('menu.permission', 'Nom de permission (Gate) attendu, ou null.');
        }
    }

    /** @param  array<string, mixed>  $d */
    private function pivotModule(array $d, ?ModuleKind $kind): void
    {
        $pivot = $d['pivot'] ?? null;

        if ($kind !== ModuleKind::Pivot) {
            if ($pivot !== null) {
                $this->add('pivot', 'Les options de pivot sont réservées aux modules de nature pivot.');
            }

            return;
        }

        if (! is_array($pivot)) {
            $this->add('pivot', 'Options de pivot attendues.');

            return;
        }

        $this->bool($pivot, 'unique_pair', 'pivot.unique_pair');
        $this->bool($pivot, 'primary_id', 'pivot.primary_id');

        $polymorphic = 0;

        foreach (['left', 'right'] as $key) {
            $side = $pivot[$key] ?? null;
            $path = "pivot.{$key}";

            if (! is_array($side)) {
                $this->add($path, 'Côté du pivot attendu.');

                continue;
            }

            $this->bool($side, 'polymorphic', "{$path}.polymorphic");

            if (($side['polymorphic'] ?? false) === true) {
                $polymorphic++;

                continue;
            }

            $this->model($side['model'] ?? null, "{$path}.model");

            if ($this->snake($side['foreign_key'] ?? null, "{$path}.foreign_key")) {
                $this->claimColumn($side['foreign_key'], "{$path}.foreign_key", "le côté {$key} du pivot");
            }

            if (! in_array($side['on_delete'] ?? null, ['cascade', 'restrict', 'no_action'], true)) {
                $this->add("{$path}.on_delete", 'Valeur attendue : cascade, restrict, no_action.');
            }
        }

        if ($polymorphic === 2) {
            $this->add('pivot', 'Un seul côté du pivot peut être polymorphe.');
        }

        if ($polymorphic === 1 && ! is_array($d['morph'] ?? null)) {
            $this->add('morph', 'Un côté polymorphe exige les options polymorphes (nom de relation, types).');
        }

        if ($polymorphic === 0 && ($d['morph'] ?? null) !== null) {
            $this->add('morph', 'Un module pivot n\'accepte l\'option polymorphe que si l\'un de ses côtés est polymorphe.');
        }
    }

    private function morph(mixed $morph): void
    {
        if ($morph === null) {
            return;
        }

        if (! is_array($morph)) {
            $this->add('morph', 'Options polymorphes attendues.');

            return;
        }

        if ($this->snake($morph['name'] ?? null, 'morph.name')) {
            $this->claimColumn($morph['name'].'_type', 'morph.name', 'la relation polymorphe');
            $this->claimColumn($morph['name'].'_id', 'morph.name', 'la relation polymorphe');
            $this->claimMethod(Str::camel($morph['name']), 'morph.name', 'la relation polymorphe');
        }

        if (KeyType::tryFrom(is_string($morph['key_type'] ?? null) ? $morph['key_type'] : '') === null) {
            $this->add('morph.key_type', 'Valeur attendue : int, uuid, ulid.');
        }

        $this->bool($morph, 'nullable', 'morph.nullable');

        if (! is_array($morph['types'] ?? null)) {
            $this->add('morph.types', 'Liste de types attendue (vide = tout modèle).');

            return;
        }

        $models = [];

        foreach ($morph['types'] as $i => $type) {
            $path = "morph.types.{$i}";

            if (! is_array($type)) {
                $this->add($path, 'Type polymorphe attendu.');

                continue;
            }

            if ($this->model($type['model'] ?? null, "{$path}.model")) {
                if (isset($models[$type['model']])) {
                    $this->add("{$path}.model", "Le modèle {$type['model']} est déjà dans la liste.");
                }

                $models[$type['model']] = true;
            }

            $this->matches($type['class'] ?? null, self::CLASS_NAME, "{$path}.class", 'Nom de classe complet attendu (ex. App\Models\Client).', 255);
            $this->text($type['label'] ?? null, "{$path}.label");
            $this->snake($type['display_column'] ?? null, "{$path}.display_column");
        }
    }

    private function fields(mixed $fields, ?ModuleKind $kind): void
    {
        if (! is_array($fields) || ! array_is_list($fields)) {
            $this->add('fields', 'Liste de champs attendue.');

            return;
        }

        if ($fields === [] && $kind !== ModuleKind::Pivot) {
            $this->add('fields', 'Au moins un champ est requis.');
        }

        foreach ($fields as $i => $field) {
            if ($this->field($field, "fields.{$i}", pivot: false)) {
                $this->claimColumn($field['name'], "fields.{$i}.name", "le champ {$field['name']}");
            }
        }
    }

    /** Valide un champ ; retourne vrai si son nom est exploitable pour les contrôles de collision. */
    private function field(mixed $field, string $path, bool $pivot): bool
    {
        if (! is_array($field)) {
            $this->add($path, 'Champ attendu.');

            return false;
        }

        $validName = $this->snake($field['name'] ?? null, "{$path}.name");

        if ($validName && in_array($field['name'], self::SYSTEM_COLUMNS, true)) {
            $this->add("{$path}.name", "« {$field['name']} » est une colonne gérée automatiquement.");
            $validName = false;
        }

        $this->text($field['label'] ?? null, "{$path}.label");

        foreach (['nullable', 'unique', 'index', 'searchable', 'sortable', 'filterable', 'in_table', 'in_form', 'in_detail'] as $flag) {
            $this->bool($field, $flag, "{$path}.{$flag}");
        }

        $typeName = $field['type'] ?? null;

        if (! is_string($typeName) || ! $this->types->has($typeName)) {
            $this->add("{$path}.type", 'Type inconnu. Types disponibles : '.implode(', ', $this->types->names()).'.');

            return $validName;
        }

        $type = $this->types->get($typeName);

        if ($pivot && ! $type->allowedInPivot()) {
            $this->add("{$path}.type", "Le type {$typeName} n'est pas disponible dans une table pivot.");
        }

        if (! is_array($field['options'] ?? null)) {
            $this->add("{$path}.options", 'Options attendues.');
        } else {
            foreach ($type->validateOptions($field['options']) as $option => $message) {
                $this->add("{$path}.options.{$option}", $message);
            }
        }

        foreach (['unique', 'index', 'searchable', 'sortable', 'filterable'] as $modifier) {
            if (($field[$modifier] ?? false) === true && ! in_array($modifier, $type->allows(), true)) {
                $this->add("{$path}.{$modifier}", "Non disponible pour le type {$typeName}.");
            }
        }

        $default = $field['default'] ?? null;

        if ($default !== null) {
            if (! in_array('default', $type->allows(), true)) {
                $this->add("{$path}.default", "Le type {$typeName} n'accepte pas de valeur par défaut.");
            } elseif ($message = $type->validateDefault($default, is_array($field['options'] ?? null) ? $field['options'] : [])) {
                $this->add("{$path}.default", $message);
            }
        }

        return $validName;
    }

    private function relations(mixed $relations, mixed $table): void
    {
        if (! is_array($relations) || ! array_is_list($relations)) {
            $this->add('relations', 'Liste de relations attendue.');

            return;
        }

        foreach ($relations as $i => $relation) {
            $this->relation($relation, "relations.{$i}", $table);
        }
    }

    private function relation(mixed $relation, string $path, mixed $table): void
    {
        if (! is_array($relation)) {
            $this->add($path, 'Relation attendue.');

            return;
        }

        $type = RelationType::tryFrom(is_string($relation['type'] ?? null) ? $relation['type'] : '');

        if ($type === null) {
            $this->add("{$path}.type", 'Type attendu : belongsTo, hasOne, hasMany, belongsToMany.');

            return;
        }

        if ($this->matches($relation['name'] ?? null, self::METHOD, "{$path}.name", 'Nom de méthode invalide : camelCase (ex. client, tags).')) {
            $this->claimMethod($relation['name'], "{$path}.name", "la relation {$relation['name']}");
        }

        $this->text($relation['label'] ?? null, "{$path}.label");
        $this->model($relation['target'] ?? null, "{$path}.target");
        $this->snake($relation['target_table'] ?? null, "{$path}.target_table");
        $this->bool($relation, 'nullable', "{$path}.nullable");
        $this->bool($relation, 'in_table', "{$path}.in_table");
        $this->bool($relation, 'in_form', "{$path}.in_form");

        if ($type !== RelationType::BelongsTo && ($relation['in_form'] ?? false) === true) {
            $this->add("{$path}.in_form", 'Seules les relations belongsTo ont un champ de formulaire.');
        }

        match ($type) {
            RelationType::BelongsTo => $this->belongsTo($relation, $path),
            RelationType::HasOne, RelationType::HasMany => $this->snake($relation['foreign_key'] ?? null, "{$path}.foreign_key"),
            RelationType::BelongsToMany => $this->belongsToMany($relation, $path, $table),
        };
    }

    /** @param  array<string, mixed>  $relation */
    private function belongsTo(array $relation, string $path): void
    {
        if ($this->snake($relation['foreign_key'] ?? null, "{$path}.foreign_key")) {
            $this->claimColumn($relation['foreign_key'], "{$path}.foreign_key", "la relation {$relation['name']}");
        }

        if (! in_array($relation['on_delete'] ?? null, self::ON_DELETE, true)) {
            $this->add("{$path}.on_delete", 'Valeur attendue : '.implode(', ', self::ON_DELETE).'.');
        } elseif ($relation['on_delete'] === 'null' && ($relation['nullable'] ?? false) !== true) {
            $this->add("{$path}.on_delete", 'Mettre la clé à null à la suppression exige une relation facultative (nullable).');
        }

        $this->snake($relation['display'] ?? null, "{$path}.display");
    }

    /** @param  array<string, mixed>  $relation */
    private function belongsToMany(array $relation, string $path, mixed $table): void
    {
        $foreign = $relation['foreign_pivot_key'] ?? null;
        $related = $relation['related_pivot_key'] ?? null;
        $foreignValid = $this->snake($foreign, "{$path}.foreign_pivot_key");
        $relatedValid = $this->snake($related, "{$path}.related_pivot_key");

        if ($foreignValid && $relatedValid && $foreign === $related) {
            $this->add("{$path}.related_pivot_key", 'Les deux clés du pivot doivent être différentes.');
        }

        $pivot = $relation['pivot'] ?? null;

        if (! is_array($pivot)) {
            $this->add("{$path}.pivot", 'Options de pivot attendues.');

            return;
        }

        $mode = PivotMode::tryFrom(is_string($pivot['mode'] ?? null) ? $pivot['mode'] : '');

        if ($mode === null) {
            $this->add("{$path}.pivot.mode", 'Valeur attendue : none, generate, module.');

            return;
        }

        $this->bool($pivot, 'timestamps', "{$path}.pivot.timestamps");
        $fields = $pivot['fields'] ?? null;

        if (! is_array($fields) || ! array_is_list($fields)) {
            $this->add("{$path}.pivot.fields", 'Liste de champs attendue.');
            $fields = [];
        }

        if ($mode === PivotMode::None && ($fields !== [] || ($pivot['timestamps'] ?? false) === true)) {
            $this->add("{$path}.pivot.mode", 'Sans table pivot, ni champs supplémentaires ni timestamps.');
        }

        if ($mode === PivotMode::Generate && $this->snake($pivot['table'] ?? null, "{$path}.pivot.table") && $pivot['table'] === $table) {
            $this->add("{$path}.pivot.table", 'La table pivot doit différer de la table du module.');
        }

        if ($mode === PivotMode::Module) {
            $this->matches($pivot['module'] ?? null, '/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', "{$path}.pivot.module", 'Slug du module pivot attendu.');
        }

        $taken = array_flip(array_filter([$foreign, $related, 'id', 'created_at', 'updated_at'], 'is_string'));

        foreach ($fields as $j => $field) {
            $fieldPath = "{$path}.pivot.fields.{$j}";

            if ($this->field($field, $fieldPath, pivot: true)) {
                if (isset($taken[$field['name']])) {
                    $this->add("{$fieldPath}.name", "La colonne « {$field['name']} » existe déjà dans la table pivot.");
                }

                $taken[$field['name']] = true;
            }
        }
    }

    /** @param  mixed  $fields  champs du module (déjà validés) */
    private function options(mixed $options, mixed $fields): void
    {
        if (! is_array($options)) {
            $this->add('options', 'Options du module attendues.');

            return;
        }

        $this->bool($options, 'timestamps', 'options.timestamps');
        $this->bool($options, 'soft_deletes', 'options.soft_deletes');

        $perPage = $options['per_page'] ?? null;

        if (! is_int($perPage) || $perPage < 1 || $perPage > 500) {
            $this->add('options.per_page', 'Un entier entre 1 et 500 est attendu.');
        }

        $sort = $options['default_sort'] ?? null;

        if (! is_array($sort)) {
            $this->add('options.default_sort', 'Tri par défaut attendu.');

            return;
        }

        if (! in_array($sort['direction'] ?? null, ['asc', 'desc'], true)) {
            $this->add('options.default_sort.direction', 'Valeur attendue : asc ou desc.');
        }

        $sortable = ['id'];

        if (($options['timestamps'] ?? false) === true) {
            array_push($sortable, 'created_at', 'updated_at');
        }

        foreach (is_array($fields) ? $fields : [] as $field) {
            if (is_array($field) && ($field['sortable'] ?? false) === true && is_string($field['name'] ?? null)) {
                $sortable[] = $field['name'];
            }
        }

        if (! in_array($sort['field'] ?? null, $sortable, true)) {
            $this->add('options.default_sort.field', 'Colonne attendue parmi : '.implode(', ', $sortable).'.');
        }
    }
}
