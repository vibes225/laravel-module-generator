<?php

namespace App\Filters;

class InvoiceFilter extends QueryFilter
{
    protected array $searchable = [
        'number',
    ];

    protected array $sortable = [
        'id',
        'number',
        'status',
        'amount',
        'issued_at',
        'created_at',
    ];

    protected array $filters = [
        'status' => 'status',
        'is_paid' => 'is_paid',
        'client_id' => 'client_id',
    ];

    protected string $defaultSort = 'number';

    protected string $defaultDirection = 'asc';

    protected int $perPage = 15;
}
