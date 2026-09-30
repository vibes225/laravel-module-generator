<?php

namespace App\Filters;

class ClientTagFilter extends QueryFilter
{
    protected array $searchable = [
        'note',
    ];

    protected array $sortable = [
        'id',
        'note',
        'created_at',
    ];

    protected array $filters = [
        'client_id' => 'client_id',
        'tag_id' => 'tag_id',
    ];

    protected string $defaultSort = 'note';

    protected string $defaultDirection = 'asc';

    protected int $perPage = 15;
}
