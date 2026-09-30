<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields;

use Amon\ModuleGenerator\Contracts\FieldType;
use Amon\ModuleGenerator\Fields\Types\BigIntegerType;
use Amon\ModuleGenerator\Fields\Types\BooleanType;
use Amon\ModuleGenerator\Fields\Types\DateTimeType;
use Amon\ModuleGenerator\Fields\Types\DateType;
use Amon\ModuleGenerator\Fields\Types\DecimalType;
use Amon\ModuleGenerator\Fields\Types\EmailType;
use Amon\ModuleGenerator\Fields\Types\EnumType;
use Amon\ModuleGenerator\Fields\Types\FileType;
use Amon\ModuleGenerator\Fields\Types\ForeignIdType;
use Amon\ModuleGenerator\Fields\Types\ImageType;
use Amon\ModuleGenerator\Fields\Types\IntegerType;
use Amon\ModuleGenerator\Fields\Types\JsonType;
use Amon\ModuleGenerator\Fields\Types\PasswordType;
use Amon\ModuleGenerator\Fields\Types\StringType;
use Amon\ModuleGenerator\Fields\Types\TextType;
use Amon\ModuleGenerator\Fields\Types\TimeType;
use Amon\ModuleGenerator\Fields\Types\UuidType;
use InvalidArgumentException;

/** Registre des types de champs : types intégrés + types déclarés dans `module-generator.field_types`. */
final class FieldTypeRegistry
{
    /** @var array<string, FieldType> */
    private array $types = [];

    /** @param  iterable<FieldType>  $extra */
    public function __construct(iterable $extra = [])
    {
        foreach (self::builtIn() as $type) {
            $this->register($type);
        }

        foreach ($extra as $type) {
            $this->register($type);
        }
    }

    /** @return list<FieldType> */
    public static function builtIn(): array
    {
        return [
            new StringType, new TextType, new IntegerType, new BigIntegerType, new DecimalType, new BooleanType,
            new DateType, new DateTimeType, new TimeType, new EmailType, new PasswordType, new EnumType,
            new JsonType, new ForeignIdType, new UuidType, new FileType, new ImageType,
        ];
    }

    /** Construit le registre depuis la config : liste de noms de classes implémentant FieldType. */
    public static function fromClasses(array $classes): self
    {
        return new self(array_map(function (string $class) {
            if (! is_a($class, FieldType::class, true)) {
                throw new InvalidArgumentException("{$class} doit implémenter ".FieldType::class.'.');
            }

            return new $class;
        }, $classes));
    }

    public function register(FieldType $type): void
    {
        $this->types[$type->name()] = $type;
    }

    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    public function get(string $name): FieldType
    {
        return $this->types[$name] ?? throw new InvalidArgumentException("Type de champ inconnu : {$name}.");
    }

    /** @return array<string, FieldType> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->types);
    }

    /**
     * Description complète des types pour l'UI : libellé, schéma d'options, modificateurs, drapeaux par défaut.
     *
     * @return list<array<string, mixed>>
     */
    public function describe(): array
    {
        return array_values(array_map(fn (FieldType $type) => [
            'name' => $type->name(),
            'label' => $type->label(),
            'options' => $type->optionSchema(),
            'allows' => $type->allows(),
            'defaults' => $type->presentationDefaults(),
            'pivot' => $type->allowedInPivot(),
        ], $this->types));
    }
}
