<?php

namespace App\Filters;

class CategoryFilter extends QueryFilter
{
    protected array $searchable = [
        'name',
    ];

    protected array $sortable = [
        'id',
        'name',
        'position',
        'created_at',
        '_lft',
    ];

    protected array $filters = [
        'owner_type' => 'owner_type',
    ];

    protected string $defaultSort = '_lft';

    protected string $defaultDirection = 'asc';

    protected int $perPage = 15;
}
