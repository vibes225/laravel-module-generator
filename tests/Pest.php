<?php

use Amon\ModuleGenerator\Definition\DefinitionFactory;
use Amon\ModuleGenerator\Definition\DefinitionNormalizer;
use Amon\ModuleGenerator\Definition\DefinitionValidator;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Generation\DefaultGenerators;
use Amon\ModuleGenerator\Generation\Formatting\NullFormatter;
use Amon\ModuleGenerator\Generation\GenerationPlan;
use Amon\ModuleGenerator\Generation\GenerationSettings;
use Amon\ModuleGenerator\Generation\ModuleGenerator;
use Amon\ModuleGenerator\Generation\PlanExecutor;
use Amon\ModuleGenerator\Generation\PlanInspector;
use Amon\ModuleGenerator\Manifest\Manifest;
use Amon\ModuleGenerator\Manifest\ManifestFile;
use Amon\ModuleGenerator\Manifest\ManifestRepository;
use Amon\ModuleGenerator\Manifest\ManifestStatus;
use Amon\ModuleGenerator\ModuleService;
use Amon\ModuleGenerator\Naming\Inflector;
use Amon\ModuleGenerator\Registry\ModuleRegistry;
use Amon\ModuleGenerator\Removal\Remover;
use Amon\ModuleGenerator\Stubs\StubRenderer;
use Amon\ModuleGenerator\Stubs\StubResolver;
use Amon\ModuleGenerator\Support\Hasher;
use Amon\ModuleGenerator\Tests\TestCase;
use Amon\ModuleGenerator\Tests\UiTestCase;

uses(TestCase::class)->in('Feature');

function definitionFactory(bool $french = false): DefinitionFactory
{
    $types = new FieldTypeRegistry;

    return new DefinitionFactory(
        new DefinitionNormalizer($types, new Inflector($french)),
        new DefinitionValidator($types),
    );
}

function normalizeDefinition(array $input, bool $french = false): array
{
    return (new DefinitionNormalizer(new FieldTypeRegistry, new Inflector($french)))->normalize($input);
}

/** Erreurs de validation d'une entrée brute (après normalisation). */
function definitionErrors(array $input): array
{
    return definitionFactory()->errors($input);
}

/** Définition minimale valide, complétée par $overrides. */
function clientInput(array $overrides = []): array
{
    return array_replace([
        'singular' => 'Client',
        'fields' => [
            ['name' => 'name', 'type' => 'string'],
        ],
    ], $overrides);
}

function expectInvalid(array $input, string $path): void
{
    $errors = definitionErrors($input);

    expect($errors)->toHaveKey($path, message: 'Erreur attendue sur '.$path.', obtenu : '.json_encode($errors, JSON_UNESCAPED_UNICODE));
}

function expectValid(array $input): void
{
    expect(definitionErrors($input))->toBe([]);
}

function sandboxPath(): string
{
    $path = str_replace(DIRECTORY_SEPARATOR, '/', sys_get_temp_dir()).'/mg-'.bin2hex(random_bytes(5));
    mkdir($path, 0777, true);

    return $path;
}

function manifestFor(array $input, ManifestStatus $status = ManifestStatus::Complete): Manifest
{
    $definition = definitionFactory()->make($input);

    return Manifest::for($definition, $status, ['generator' => 'dev'], '2026-09-30T10:00:00+00:00', [
        new ManifestFile('app/Models/'.$definition->model.'.php', 'model', Hasher::hash('<?php')),
    ]);
}

/** Pile complète du moteur sur un projet bac à sable (sans conteneur). */
function moduleService(string $base, string $convention = 'breeze'): ModuleService
{
    $types = new FieldTypeRegistry;
    $normalizer = new DefinitionNormalizer($types, new Inflector);
    $manifests = new ManifestRepository($base.'/.module-generator');
    $registry = new ModuleRegistry($manifests);

    return new ModuleService(
        $normalizer,
        new DefinitionFactory($normalizer, new DefinitionValidator($types)),
        new ModuleGenerator(DefaultGenerators::all(), $types, new StubRenderer(new StubResolver(dirname(__DIR__).'/stubs'))),
        $registry,
        $manifests,
        new PlanInspector($base, $registry),
        new PlanExecutor($base, $manifests, new NullFormatter, ['generator' => 'test']),
        fn () => new GenerationSettings($convention, 13, 'App\Models', new DateTimeImmutable('2026-09-30 10:00:00')),
        new Remover($base, $manifests),
    );
}

/** Projet bac à sable « installé » (QueryFilter présent). */
function installedSandbox(): string
{
    $base = sandboxPath();
    mkdir($base.'/app/Filters', 0777, true);
    file_put_contents($base.'/app/Filters/QueryFilter.php', '<?php');
    file_put_contents($base.'/composer.json', '{"require": {}}');

    return $base;
}

/** Plan d'une entrée brute, sans inspection du projet. */
function planFor(array $input, string $convention = 'breeze'): GenerationPlan
{
    $types = new FieldTypeRegistry;
    $generator = new ModuleGenerator(DefaultGenerators::all(), $types, new StubRenderer(new StubResolver(dirname(__DIR__).'/stubs')));

    return $generator->plan(definitionFactory()->make($input), new GenerationSettings($convention, 13, 'App\Models', new DateTimeImmutable('2026-09-30 10:00:00')));
}

uses(UiTestCase::class)->in('Http');
