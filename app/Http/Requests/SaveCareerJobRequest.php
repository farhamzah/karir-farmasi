<?php

namespace App\Http\Requests;

use App\Models\CompanyUser;
use Illuminate\Foundation\Http\FormRequest;

class SaveCareerJobRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->routeIs('company.jobs.*')) {
            /** @var CompanyUser|null $user */
            $user = $this->attributes->get(CompanyUser::class);
            $this->merge(['employer_display_name' => $user?->company?->display_name]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employer_display_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,internship,project,temporary'],
            'work_mode' => ['required', 'in:onsite,hybrid,remote'],
            'city' => ['nullable', 'string', 'max:120'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'responsibilities' => ['nullable', 'string', 'max:10000'],
            'education_requirement' => ['nullable', 'string', 'max:255'],
            'experience_requirement' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'gte:salary_min'],
            'salary_visible' => ['sometimes', 'boolean'],
            'openings' => ['nullable', 'integer', 'between:1,10000'],
            'application_method' => ['required', 'in:internal,external_url,email_instruction,email'],
            'external_apply_url' => ['nullable', 'required_if:application_method,external_url', 'url:http,https', 'max:2048'],
            'external_apply_email' => ['nullable', 'required_if:application_method,email_instruction,email', 'email', 'max:255'],
            'application_instruction' => ['nullable', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'review_at' => ['nullable', 'date', 'after_or_equal:today'],
            'tags' => ['nullable', 'string', 'max:2000'],
            'source_type' => ['nullable', 'in:company_direct,campus_input,partner,public_source'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'url:http,https', 'max:2048'],
            'received_at' => ['nullable', 'date'],
            'source_verified' => ['sometimes', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'source_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'flyer' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=500,max_width=5000,max_height=7000'],
            'flyer_alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
