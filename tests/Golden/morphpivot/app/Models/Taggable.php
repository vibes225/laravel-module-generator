<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Taggable extends MorphPivot
{
    use HasFactory;

    /**
     * Types autorisés pour la relation taggable : classe => libellé.
     */
    public const TAGGABLE_TYPES = [
        Post::class => 'Post',
        Video::class => 'Video',
    ];

    protected $table = 'taggables';

    public $incrementing = true;

    protected $fillable = [
        'tag_id',
        'taggable_type',
        'taggable_id',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
