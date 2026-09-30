<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Contracts\FieldType;
use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Definition\ModuleDefinition;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Stubs\StubRenderer;

final readonly class GenerationContext
{
    public ModuleNames $names;

    public function __construct(
        public ModuleDefinition $definition,
        public FieldTypeRegistry $types,
        public GenerationSettings $settings,
        public StubRenderer $renderer,
    ) {
        $this->names = new ModuleNames($definition, $settings);
    }

    public function type(FieldDefinition $field): FieldType
    {
        return $this->types->get($field->type);
    }

    /** @param  array<string, string>  $values */
    public function render(string $stub, array $values): string
    {
        return $this->renderer->render($stub, $values);
    }
}
