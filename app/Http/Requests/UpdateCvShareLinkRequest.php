<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCvShareLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'active' => ['sometimes', 'boolean'],
            'allow_pdf_download' => ['sometimes', 'boolean'],
            'follow_latest_published' => ['sometimes', 'boolean'],
        ];
    }
}
