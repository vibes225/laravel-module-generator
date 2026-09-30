<?php

namespace App\Http\Requests\Admin;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:255', Rule::unique('invoices', 'number')],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'amount' => ['required', 'numeric', 'decimal:0,2'],
            'issued_at' => ['required', 'date'],
            'attachment' => ['nullable', File::default()->types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'])->max(2048)],
            'is_paid' => ['required', 'boolean'],
            'secret' => ['required', 'string', 'min:8'],
            'meta' => ['nullable', 'json'],
            'notes' => ['nullable', 'string'],
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'tags' => ['array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'number' => 'Number',
            'status' => 'Status',
            'amount' => 'Amount',
            'issued_at' => 'Issued at',
            'attachment' => 'Attachment',
            'is_paid' => 'Is paid',
            'secret' => 'Secret',
            'meta' => 'Meta',
            'notes' => 'Notes',
            'client_id' => 'Client',
            'tags' => 'Tags',
        ];
    }
}
