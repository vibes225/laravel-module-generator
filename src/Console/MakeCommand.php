<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Console;

use Amon\ModuleGenerator\Console\Concerns\PrintsPlans;
use Amon\ModuleGenerator\Definition\InvalidDefinition;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\ModuleService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final class MakeCommand extends Command
{
    use PrintsPlans;

    protected $signature = 'module-generator:make
        {name? : Libellé singulier (ex. "Client", "Client contact")}
        {--field=* : nom:type[(options)][:modificateurs], ex. status:enum(draft,sent):default=draft}
        {--relation=* : type:Cible[:options], ex. belongsTo:Client ou belongsToMany:Tag:pivot=generate:form}
        {--pivot-of= : Module pivot reliant deux modèles, ex. Client,Tag}
        {--no-id : Module pivot sans colonne id}
        {--no-unique-pair : Module pivot sans unicité du couple}
        {--morph= : Relation polymorphe, ex. commentable}
        {--morph-types= : Types polymorphes autorisés, ex. Client,Dossier:reference (colonne affichée, name par défaut)}
        {--morph-key=int : Type de clé polymorphe : int, uuid, ulid}
        {--morph-nullable : Rattachement polymorphe facultatif}
        {--tree : Structure en arbre (kalnoy/nestedset)}
        {--soft-deletes : Suppression logique}
        {--no-timestamps : Sans created_at / updated_at}
        {--model= : Nom du modèle}
        {--table= : Nom de la table}
        {--slug= : Slug (URL, routes)}
        {--plural= : Libellé pluriel}
        {--icon= : Icône du menu}
        {--group= : Groupe du menu}
        {--order= : Ordre dans le menu}
        {--permission= : Permission (Gate) du menu}
        {--no-menu : Sans entrée de menu}
        {--per-page= : Éléments par page}
        {--sort= : Tri par défaut : champ[:desc]}
        {--from= : Régénérer depuis une définition archivée (slug, nom d\'archive ou chemin)}
        {--json= : Fichier JSON de définition}
        {--dry-run : Affiche le plan sans rien écrire}
        {--show-content : Affiche le contenu des fichiers (avec --dry-run)}
        {--format=text : text ou json (plan au format JSON)}';

    protected $description = 'Génère un module CRUD (modèle, migration, contrôleur, requests, pages React...).';

    public function handle(ModuleService $service, FieldTypeRegistry $types): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->refuseInProduction($dryRun)) {
            return self::FAILURE;
        }

        try {
            $input = $this->buildInput($service, $types);
            $definition = $service->definition($input);
        } catch (InvalidDefinition $e) {
            $this->printErrors($e->errors);

            return self::FAILURE;
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $plan = $service->plan($definition);

        if ($this->option('format') === 'json') {
            $this->line((string) json_encode($plan->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $plan->isExecutable() || $dryRun ? self::SUCCESS : self::FAILURE;
        }

        $this->printPlan($plan, (bool) $this->option('show-content'));

        if ($dryRun) {
            $this->components->info('Dry-run : aucun fichier écrit.');

            return self::SUCCESS;
        }

        if (! $plan->isExecutable()) {
            $this->components->error('Génération impossible : aucun fichier écrit (pas d\'écrasement possible).');

            return self::FAILURE;
        }

        if ($this->input->isInteractive() && ! confirm('Générer ces fichiers ?', default: true)) {
            return self::FAILURE;
        }

        try {
            $manifest = $service->generate($plan);
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($service->warnings() as $warning) {
            $this->components->warn($warning);
        }

        $this->components->info(count($manifest->files).' fichier(s) générés. Manifest : .module-generator/modules/'.$manifest->slug.'.json');
        $this->components->bulletList(['php artisan migrate', 'npm run build (ou npm run dev)']);

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function buildInput(ModuleService $service, FieldTypeRegistry $types): array
    {
        if (is_string($from = $this->option('from'))) {
            return $service->archivedInput($from) ?? throw new InvalidArgumentException("Aucune définition archivée pour « {$from} ».");
        }

        if (is_string($json = $this->option('json'))) {
            $input = is_file($json) ? json_decode((string) file_get_contents($json), true) : null;

            return is_array($input) ? $input : throw new InvalidArgumentException("Fichier JSON illisible : {$json}.");
        }

        $parser = new CliDefinitionParser;
        $fields = array_map($parser->field(...), (array) $this->option('field'));
        $name = $this->argument('name');

        if ($this->input->isInteractive() && ($name === null || ($fields === [] && ! $this->option('pivot-of')))) {
            [$name, $fields] = $this->interactive($types, $name, $fields);
        }

        $input = array_filter([
            'singular' => $name,
            'name' => $this->option('plural'),
            'model' => $this->option('model'),
            'table' => $this->option('table'),
            'slug' => $this->option('slug'),
            'icon' => $this->option('icon'),
        ], fn ($value) => $value !== null);

        $input['fields'] = $fields;
        $input['relations'] = array_map($parser->relation(...), (array) $this->option('relation'));
        $input['tree'] = (bool) $this->option('tree');
        $input['options'] = array_filter([
            'soft_deletes' => (bool) $this->option('soft-deletes'),
            'timestamps' => ! $this->option('no-timestamps'),
            'per_page' => $this->option('per-page') === null ? null : (int) $this->option('per-page'),
            'default_sort' => $this->sort(),
        ], fn ($value) => $value !== null);
        $input['menu'] = array_filter([
            'enabled' => ! $this->option('no-menu'),
            'group' => $this->option('group'),
            'order' => $this->option('order') === null ? null : (int) $this->option('order'),
            'permission' => $this->option('permission'),
        ], fn ($value) => $value !== null);

        if (is_string($pivotOf = $this->option('pivot-of'))) {
            $models = $parser->list($pivotOf);

            if (count($models) !== 2) {
                throw new InvalidArgumentException('--pivot-of attend deux modèles : Client,Tag (ou « * » pour un côté polymorphe).');
            }

            $side = fn (string $model) => $model === '*' ? ['polymorphic' => true] : ['model' => $model];
            $input['kind'] = 'pivot';
            $input['pivot'] = [
                'left' => $side($models[0]),
                'right' => $side($models[1]),
                'primary_id' => ! $this->option('no-id'),
                'unique_pair' => ! $this->option('no-unique-pair'),
            ];
        }

        if (is_string($morph = $this->option('morph'))) {
            $input['morph'] = [
                'name' => $morph,
                'key_type' => $this->option('morph-key'),
                'nullable' => (bool) $this->option('morph-nullable'),
                'types' => array_map(function (string $type) {
                    [$model, $column] = array_pad(explode(':', $type, 2), 2, null);

                    return array_filter(['model' => $model, 'display_column' => $column], fn ($value) => $value !== null);
                }, $parser->list($this->option('morph-types'))),
            ];
        }

        return $input;
    }

    /** @return array{field: string, direction: string}|null */
    private function sort(): ?array
    {
        $sort = $this->option('sort');

        if (! is_string($sort) || $sort === '') {
            return null;
        }

        [$field, $direction] = array_pad(explode(':', $sort, 2), 2, 'asc');

        return ['field' => $field, 'direction' => $direction];
    }

    /**
     * Saisie guidée (Laravel Prompts) quand aucun champ n'est fourni.
     *
     * @param  list<array<string, mixed>>  $fields
     * @return array{string, list<array<string, mixed>>}
     */
    private function interactive(FieldTypeRegistry $types, ?string $name, array $fields): array
    {
        $name ??= text('Libellé singulier du module', placeholder: 'Client', required: true);
        $choices = [];

        foreach ($types->all() as $type) {
            $choices[$type->name()] = $type->label().' ('.$type->name().')';
        }

        while (true) {
            $field = text('Nom du champ (vide pour terminer)', placeholder: 'name');

            if ($field === '') {
                break;
            }

            $type = (string) select('Type de « '.$field.' »', $choices, default: 'string');
            $entry = ['name' => $field, 'type' => $type, 'nullable' => confirm('Facultatif (nullable) ?', default: false)];

            if ($type === 'enum') {
                $entry['options'] = ['values' => (new CliDefinitionParser)->list(text('Valeurs (séparées par des virgules)', required: true))];
            }

            if ($type === 'foreignId') {
                $entry['options'] = ['references' => text('Table référencée', required: true)];
            }

            $fields[] = $entry;
        }

        return [$name, $fields];
    }
}
