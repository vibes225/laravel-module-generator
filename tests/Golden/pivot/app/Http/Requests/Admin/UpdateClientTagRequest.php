<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientTagRequest extends FormRequest
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
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'tag_id' => ['required', 'integer', Rule::exists('tags', 'id'), Rule::unique('client_tag', 'tag_id')
                ->where('client_id', $this->input('client_id'))
                ->ignore($this->route('client_tag'))],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'Client',
            'tag_id' => 'Tag',
            'note' => 'Note',
        ];
    }
}
