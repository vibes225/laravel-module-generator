<?php

namespace App\Filters;

class FollowFilter extends QueryFilter
{
    protected array $searchable = [];

    protected array $sortable = [
        'created_at',
        'id',
    ];

    protected array $filters = [
        'follower_id' => 'follower_id',
        'followed_id' => 'followed_id',
    ];

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'asc';

    protected int $perPage = 15;
}
