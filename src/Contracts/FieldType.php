<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Contracts;

use Amon\ModuleGenerator\Definition\FieldDefinition;
use Amon\ModuleGenerator\Generation\ModuleNames;

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

    // --- Génération (phases 6 et 7). Tout le code produit est autonome : aucune référence au package.

    /** Colonne de migration sans `;` : `$table->string('name', 100)->nullable()->unique()`. */
    public function migrationColumn(FieldDefinition $field): string;

    /** Expression PHP du cast Eloquent (`'boolean'`, `InvoiceStatus::class`), ou null. */
    public function cast(FieldDefinition $field, ModuleNames $names): ?string;

    /**
     * Règles de validation (expressions PHP), présence (required/nullable) comprise.
     *
     * @return list<string>
     */
    public function rules(FieldDefinition $field, ModuleNames $names, bool $update): array;

    /**
     * Classes utilisées par le cast, les règles et la factory.
     *
     * @return list<string>
     */
    public function imports(FieldDefinition $field, ModuleNames $names): array;

    /** Expression PHP de la valeur de factory. */
    public function factoryValue(FieldDefinition $field, ModuleNames $names): string;

    /** Nom du fragment de formulaire (`stubs/fragments/form/<nom>.stub`). */
    public function formFragment(FieldDefinition $field): string;

    /**
     * Variables propres au fragment (en plus de NAME, LABEL, REQUIRED), déjà échappées pour du JSX.
     *
     * @return array<string, string>
     */
    public function formVariables(FieldDefinition $field): array;

    /** Valeur initiale JavaScript du champ dans le formulaire (`record` = enregistrement édité ou null). */
    public function formInitial(FieldDefinition $field): string;

    /** Expression JSX d'affichage (tableau, fiche) ; `$variable` vaut `row` ou `record`. */
    public function display(FieldDefinition $field, string $variable): string;

    /**
     * Fonctions du kit utilisées par display() (`formatDate`, `Badge`...).
     *
     * @return list<string>
     */
    public function displayImports(FieldDefinition $field): array;

    /** Expression PHP des options [{value, label}] (selects, filtres), ou null pour les valeurs distinctes en base. */
    public function optionsExpression(FieldDefinition $field, ModuleNames $names): ?string;

    /** Le champ est-il un fichier (formulaire multipart, stockage) ? */
    public function isFile(): bool;
}
