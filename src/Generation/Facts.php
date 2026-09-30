<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Definition\ModuleDefinition;
use Amon\ModuleGenerator\Definition\PivotSide;
use Amon\ModuleGenerator\Definition\RelationDefinition;
use Amon\ModuleGenerator\Definition\RelationType;
use Amon\ModuleGenerator\Fields\FieldTypeRegistry;
use Amon\ModuleGenerator\Fields\FileLike;
use Illuminate\Support\Str;

/** Faits dérivés d'une définition, partagés par plusieurs générateurs. */
final class Facts
{
    /** Colonne représentant un enregistrement (libellés, parent de l'arbre) : premier champ texte, sinon id. */
    public static function titleField(ModuleDefinition $definition): string
    {
        foreach ($definition->fields as $field) {
            if (in_array($field->type, ['string', 'email'], true)) {
                return $field->name;
            }
        }

        return 'id';
    }

    /** @return list<FieldDefinition> */
    public static function formFields(ModuleDefinition $definition): array
    {
        return array_values(array_filter($definition->fields, fn (FieldDefinition $field) => $field->inForm));
    }

    /** @return list<FieldDefinition> */
    public static function fieldsOfType(ModuleDefinition $definition, string $type, bool $inFormOnly = true): array
    {
        return array_values(array_filter(
            $definition->fields,
            fn (FieldDefinition $field) => $field->type === $type && (! $inFormOnly || $field->inForm),
        ));
    }

    /** @return list<FieldDefinition> */
    public static function fileFields(ModuleDefinition $definition, FieldTypeRegistry $types): array
    {
        return array_values(array_filter(
            $definition->fields,
            fn (FieldDefinition $field) => $types->get($field->type) instanceof FileLike,
        ));
    }

    /** @return list<RelationDefinition> */
    public static function relations(ModuleDefinition $definition, RelationType $type): array
    {
        return array_values(array_filter($definition->relations, fn (RelationDefinition $relation) => $relation->type === $type));
    }

    /** @return list<RelationDefinition> relations belongsToMany saisies dans le formulaire */
    public static function syncedRelations(ModuleDefinition $definition): array
    {
        return array_values(array_filter(
            self::relations($definition, RelationType::BelongsToMany),
            fn (RelationDefinition $relation) => $relation->inForm,
        ));
    }

    /** Méthode de relation d'un côté de pivot : `client_id` => `client`. */
    public static function sideMethod(PivotSide $side): string
    {
        return Str::camel(Str::beforeLast((string) $side->foreignKey, '_id'));
    }

    /** Constante des types polymorphes du modèle : `COMMENTABLE_TYPES`. */
    public static function morphConstant(string $morphName): string
    {
        return strtoupper($morphName).'_TYPES';
    }

    /** Relation polymorphe exposée dans les formulaires (types connus). */
    public static function morphInForm(ModuleDefinition $definition): bool
    {
        return $definition->morph !== null && $definition->morph->types !== [];
    }

    /** Module pivot sans colonne id : CRUD réduit (liste, création, suppression). */
    public static function isPair(ModuleDefinition $definition): bool
    {
        return $definition->pivot !== null && ! $definition->pivot->primaryId;
    }
}
