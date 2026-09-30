<?php

namespace App\Http\Requests\Admin;

use App\Models\Taggable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaggableRequest extends FormRequest
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
            'tag_id' => ['required', 'integer', Rule::exists('tags', 'id'), Rule::unique('taggables', 'tag_id')
                ->where('taggable_type', $this->input('taggable_type'))
                ->where('taggable_id', $this->input('taggable_id'))],
            'taggable_type' => ['required', Rule::in(array_keys(Taggable::TAGGABLE_TYPES))],
            'taggable_id' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                $type = $this->input('taggable_type');

                if (! is_string($type) || ! array_key_exists($type, Taggable::TAGGABLE_TYPES) || ! $type::query()->whereKey($value)->exists()) {
                    $fail('validation.exists')->translate();
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tag_id' => 'Tag',
            'taggable_type' => 'Type',
            'taggable_id' => 'Élément',
        ];
    }
}
