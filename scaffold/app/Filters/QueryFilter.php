<?php

namespace App\Filters;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Base des filtres de liste : recherche, filtres, tri en liste blanche et pagination avec query string.
 * Paramètres : ?search=…&sort=…&direction=asc|desc&filter[nom]=…&page=…
 *
 * Un filtre ou un tri peut être personnalisé par une méthode filterNom(Builder, valeur) ou sortNom(Builder, direction).
 */
abstract class QueryFilter
{
    /** @var list<string> colonnes recherchées (LIKE) */
    protected array $searchable = [];

    /** @var list<string> colonnes triables */
    protected array $sortable = [];

    /** @var array<string, string> filtre => colonne */
    protected array $filters = [];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'asc';

    protected int $perPage = 15;

    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $search = $this->search();

        if ($search !== '' && $this->searchable !== []) {
            $query->where(function (Builder $query) use ($search) {
                foreach ($this->searchable as $column) {
                    $query->orWhere($query->qualifyColumn($column), 'like', '%'.$search.'%');
                }
            });
        }

        foreach ($this->activeFilters() as $name => $value) {
            $method = 'filter'.Str::studly($name);

            if (method_exists($this, $method)) {
                $this->{$method}($query, $value);
            } else {
                $query->where($query->qualifyColumn($this->filters[$name]), $value);
            }
        }

        $sort = $this->sort();
        $method = 'sort'.Str::studly($sort);

        if (method_exists($this, $method)) {
            $this->{$method}($query, $this->direction());
        } else {
            $query->orderBy($query->qualifyColumn($sort), $this->direction());
        }

        return $query;
    }

    public function paginate(Builder $query): LengthAwarePaginator
    {
        return $this->apply($query)->paginate($this->perPage)->withQueryString();
    }

    /**
     * État courant pour le frontend (composants Filters et DataTable).
     *
     * @return array{search: string, sort: string, direction: string, filter: array<string, string>}
     */
    public function state(): array
    {
        return [
            'search' => $this->search(),
            'sort' => $this->sort(),
            'direction' => $this->direction(),
            'filter' => $this->activeFilters(),
        ];
    }

    protected function search(): string
    {
        return trim((string) $this->request->query('search', ''));
    }

    protected function sort(): string
    {
        $sort = $this->request->query('sort');

        return is_string($sort) && in_array($sort, $this->sortable, true) ? $sort : $this->defaultSort;
    }

    protected function direction(): string
    {
        $direction = $this->request->query('direction');

        return in_array($direction, ['asc', 'desc'], true) ? $direction : $this->defaultDirection;
    }

    /** @return array<string, string> */
    protected function activeFilters(): array
    {
        $input = $this->request->query('filter', []);
        $active = [];

        foreach (is_array($input) ? $input : [] as $name => $value) {
            if (isset($this->filters[$name]) && is_scalar($value) && (string) $value !== '') {
                $active[$name] = (string) $value;
            }
        }

        return $active;
    }
}
