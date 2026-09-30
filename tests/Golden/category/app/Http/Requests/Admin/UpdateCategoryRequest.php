<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'owner_type' => ['nullable', 'required_with:owner_id', Rule::in(array_keys(Category::OWNER_TYPES))],
            'owner_id' => ['nullable', 'required_with:owner_type', function (string $attribute, mixed $value, Closure $fail): void {
                $type = $this->input('owner_type');

                if (! is_string($type) || ! array_key_exists($type, Category::OWNER_TYPES) || ! $type::query()->whereKey($value)->exists()) {
                    $fail('validation.exists')->translate();
                }
            }],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id'), Rule::notIn([$category->getKey(), ...$category->descendants()->pluck('id')])],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:0'],
            'cover' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'owner_type' => 'Type',
            'owner_id' => 'Élément',
            'parent_id' => 'Parent',
            'name' => 'Name',
            'position' => 'Position',
            'cover' => 'Cover',
        ];
    }
}
