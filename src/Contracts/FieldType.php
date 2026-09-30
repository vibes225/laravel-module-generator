<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Contracts;

/**
 * Contrat unique des types de champs. Phase 3 : identité, schéma d'options exposé à l'UI, validation.
 * Les méthodes de génération (colonne, cast, règles, composants) s'y ajouteront aux phases 6 et 7.
 */
interface FieldType
{
    /** Identifiant utilisé dans la définition (`string`, `foreignId`, ...). */
    public function name(): string;

    /** Libellé affiché dans l'interface. */
    public function label(): string;

    /**
     * Schéma des options propres au type, exposé à l'UI.
     *
     * @return array<string, array<string, mixed>>
     */
    public function optionSchema(): array;

    /**
     * Modificateurs acceptés parmi : unique, index, default, searchable, sortable, filterable.
     *
     * @return list<string>
     */
    public function allows(): array;

    /**
     * Valeurs par défaut des drapeaux : searchable, sortable, filterable, in_table, in_form, in_detail.
     *
     * @return array<string, bool>
     */
    public function presentationDefaults(): array;

    /** Le type peut-il servir de champ supplémentaire d'une table pivot ? */
    public function allowedInPivot(): bool;

    /**
     * Complète les options avec leurs valeurs par défaut.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function normalizeOptions(array $options): array;

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, string> messages d'erreur par option
     */
    public function validateOptions(array $options): array;

    /**
     * Valide la valeur par défaut d'un champ ; retourne un message d'erreur ou null.
     *
     * @param  array<string, mixed>  $options
     */
    public function validateDefault(mixed $value, array $options): ?string;
}
