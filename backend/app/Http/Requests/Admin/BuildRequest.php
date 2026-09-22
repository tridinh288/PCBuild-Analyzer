<?php

namespace App\Http\Requests\Admin;

use App\Enums\BuildPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Template details. Components are managed separately (BuildItemsRequest).
 */
class BuildRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('builds', 'slug')->ignore($this->route('build')?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'purpose' => ['required', Rule::enum(BuildPurpose::class)],
            'is_featured' => ['sometimes', 'boolean'],
        ];
    }
}
