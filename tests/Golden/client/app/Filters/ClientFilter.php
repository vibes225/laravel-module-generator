<?php

namespace App\Filters;

class ClientFilter extends QueryFilter
{
    protected array $searchable = [
        'name',
        'email',
    ];

    protected array $sortable = [
        'id',
        'name',
        'email',
        'status',
        'created_at',
    ];

    protected array $filters = [
        'vip' => 'vip',
        'status' => 'status',
    ];

    protected string $defaultSort = 'name';

    protected string $defaultDirection = 'asc';

    protected int $perPage = 15;
}
