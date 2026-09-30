<?php

namespace App\Filters;

class TaggableFilter extends QueryFilter
{
    protected array $searchable = [];

    protected array $sortable = [
        'id',
        'created_at',
    ];

    protected array $filters = [
        'tag_id' => 'tag_id',
        'taggable_type' => 'taggable_type',
    ];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'asc';

    protected int $perPage = 15;
}
