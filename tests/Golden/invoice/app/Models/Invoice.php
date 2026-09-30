<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'number',
        'status',
        'amount',
        'issued_at',
        'attachment',
        'is_paid',
        'secret',
        'meta',
        'notes',
        'client_id',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $appends = [
        'attachment_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'amount' => 'decimal:2',
            'issued_at' => 'date:Y-m-d',
            'is_paid' => 'boolean',
            'secret' => 'hashed',
            'meta' => 'array',
        ];
    }

    protected function attachmentUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->attachment ? Storage::disk('public')->url($this->attachment) : null,
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'invoice_tag', 'invoice_id', 'tag_id')
            ->withTimestamps();
    }
}
