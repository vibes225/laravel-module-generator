<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Generators;

use Amon\ModuleGenerator\Contracts\Generator;
use Amon\ModuleGenerator\Generation\Code;
use Amon\ModuleGenerator\Generation\Facts;
use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;
use Amon\ModuleGenerator\Support\Escaper;
use Illuminate\Support\Str;

/** Pages Inertia React (JSX) : Index, Create, Edit, Show et le formulaire partagé. N'utilisent que le kit publié. */
final class PageGenerator implements Generator
{
    private const KIT = 'components/admin';

    public function applies(GenerationContext $context): bool
    {
        return true;
    }

    public function generate(GenerationContext $context): array
    {
        $names = $context->names;
        $files = [
            new PlannedFile($names->pagePath('Index'), $this->index($context), 'page', 'Page liste'),
            new PlannedFile($names->pagePath('Create'), $this->simplePage($context, 'create'), 'page', 'Page création'),
        ];

        if (! Facts::isPair($context->definition)) {
            $files[] = new PlannedFile($names->pagePath('Edit'), $this->simplePage($context, 'edit'), 'page', 'Page modification');
            $files[] = new PlannedFile($names->pagePath('Show'), $this->show($context), 'page', 'Page fiche');
        }

        $files[] = new PlannedFile($names->formPath(), (new FormBuilder)->build($context, $this->kitPath(2)), 'component', 'Formulaire');

        return $files;
    }

    /** Chemin relatif vers le kit depuis `resources/js/<pages>/<Module>` (+ sous-dossiers). */
    private function kitPath(int $depth): string
    {
        return str_repeat('../', $depth + 1).self::KIT;
    }

    private function simplePage(GenerationContext $context, string $stub): string
    {
        return $context->render('pages/'.$stub, [
            'KIT_PATH' => $this->kitPath(1),
            'FORM' => Str::studly($context->names->formComponent),
            'TITLE' => Escaper::js($context->definition->name),
            'ROUTE' => $context->names->routeName,
        ]);
    }

    private function index(GenerationContext $context): string
    {
        $definition = $context->definition;
        $pair = Facts::isPair($definition);
        $columns = new ColumnBuilder($context);
        $pivot = $definition->pivot;

        $destroy = $pair
            ? '{ '.$pivot->left->foreignKey.': toDelete.'.$pivot->left->foreignKey.', '.$pivot->right->foreignKey.': toDelete.'.$pivot->right->foreignKey.' }'
            : 'toDelete.id';
        $rowKey = $pair ? '(row) => `${row.'.$pivot->left->foreignKey.'}-${row.'.$pivot->right->foreignKey.'}`' : "'id'";
        $actions = $pair ? '' : Code::indent(implode("\n", [
            "<Button size=\"sm\" variant=\"ghost\" href={route('{$context->names->routeName}.show', row.id)} aria-label=\"Voir\">",
            '    <Eye className="h-4 w-4" />',
            '</Button>',
            "<Button size=\"sm\" variant=\"ghost\" href={route('{$context->names->routeName}.edit', row.id)} aria-label=\"Modifier\">",
            '    <Pencil className="h-4 w-4" />',
            '</Button>',
        ]), 6)."\n";

        $columnList = $columns->columns();
        $filterList = $columns->filters();
        $kit = array_merge(['AdminLayout', 'Button', 'ConfirmDialog', 'DataTable', 'Filters', 'Pagination'], $columns->imports());
        $usesOptions = str_contains(implode('', $columnList).implode('', $filterList), 'options.');

        return $context->render('pages/index', [
            'ICONS' => $pair ? 'Plus, Trash2' : 'Eye, Pencil, Plus, Trash2',
            'KIT' => Code::indent(implode("\n", array_map(fn (string $name) => $name.',', self::sortImports($kit))), 1),
            'KIT_PATH' => $this->kitPath(1),
            'TITLE' => Escaper::js($definition->name),
            'HELPERS' => $definition->tree
                ? "\n    const indent = (row) => (filters.sort === '_lft' && !filters.search ? `\${(row.depth ?? 0) * 1.5}rem` : 0);\n"
                : '',
            'COLUMNS' => Code::indent(implode("\n", $columnList), 2),
            'PROPS' => $usesOptions ? 'records, filters, options' : 'records, filters',
            'ROUTE' => $context->names->routeName,
            'DESTROY_PARAMETERS' => $destroy,
            'SEARCH' => array_filter($definition->fields, fn ($field) => $field->searchable) === [] ? 'false' : 'true',
            'FILTERS' => Code::indent(implode("\n", $filterList), 5),
            'ROW_KEY' => $rowKey,
            'ROW_ACTIONS' => $actions,
        ]);
    }

    private function show(GenerationContext $context): string
    {
        $definition = $context->definition;
        $columns = new ColumnBuilder($context);
        $items = [];

        $details = $columns->details();
        $usesOptions = false;

        foreach ($details as [$label, $value]) {
            $usesOptions = $usesOptions || str_contains($value, 'options.');
            $items[] = rtrim($context->render('pages/detail-item', ['LABEL' => Escaper::jsx($label), 'VALUE' => $value]));
        }

        $title = Facts::titleField($definition);

        return $context->render('pages/show', [
            'KIT' => implode(', ', self::sortImports(['AdminLayout', 'Button', ...$columns->imports()])),
            'KIT_PATH' => $this->kitPath(1),
            'TITLE' => Escaper::js($definition->name),
            'RECORD_TITLE' => $title === 'id' ? '`${TITLE} #${record.id}`' : "record.{$title} ?? TITLE",
            'ROUTE' => $context->names->routeName,
            'ITEMS' => Code::indent(implode("\n", $items), 5),
            'PROPS' => $usesOptions ? 'record, options' : 'record',
        ]);
    }

    /**
     * Composants (majuscule) puis fonctions, par ordre alphabétique.
     *
     * @param  list<string>  $imports
     * @return list<string>
     */
    public static function sortImports(array $imports): array
    {
        $imports = array_values(array_unique($imports));
        usort($imports, fn (string $a, string $b) => [ctype_lower($a[0]), $a] <=> [ctype_lower($b[0]), $b]);

        return $imports;
    }
}
