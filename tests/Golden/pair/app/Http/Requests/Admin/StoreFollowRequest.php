<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowRequest extends FormRequest
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
            'follower_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'followed_id' => ['required', 'integer', Rule::exists('users', 'id'), Rule::unique('user_user', 'followed_id')
                ->where('follower_id', $this->input('follower_id'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'follower_id' => 'User',
            'followed_id' => 'User',
        ];
    }
}
