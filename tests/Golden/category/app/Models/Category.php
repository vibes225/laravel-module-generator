<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use HasFactory, NodeTrait;

    /**
     * Types autorisés pour la relation owner : classe => libellé.
     */
    public const OWNER_TYPES = [
        User::class => 'User',
        Client::class => 'Client',
    ];

    protected $table = 'categories';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'parent_id',
        'name',
        'position',
        'cover',
    ];

    protected $appends = [
        'cover_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->cover ? Storage::disk('public')->url($this->cover) : null,
        );
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
