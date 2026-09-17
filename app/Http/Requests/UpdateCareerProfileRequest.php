<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareerProfileRequest extends FormRequest
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
            'professional_name' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'professional_summary' => ['nullable', 'string', 'max:5000'],
            'professional_email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:2048'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:2048'],
            'open_to_work' => ['required', 'boolean'],
            'profile_visibility' => ['required', Rule::in(['private', 'searchable'])],
            'section_visibility' => ['nullable', 'array:education,experience,skills,certifications,organizations,projects,publications,languages'],
            'section_visibility.*' => ['boolean'],
        ];
    }
}
