<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Image upload for products and builds: type and size checked before anything is sent to
 * the image service (spec section 25).
 */
class ImageRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:'.implode(',', config('images.upload.mimes')),
                'max:'.config('images.upload.max_kb'),
            ],
        ];
    }
}
