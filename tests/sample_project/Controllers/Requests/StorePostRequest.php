<?php

declare(strict_types=1);

namespace Tests\Sample\Controllers\Requests;

use App\Core\FormRequest;
use App\Core\Application;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:100',
            'body'  => 'required|string|min:5',
        ];
    }
}
