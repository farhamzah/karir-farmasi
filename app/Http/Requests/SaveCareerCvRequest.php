<?php

namespace App\Http\Requests;

use App\Cv\CvFieldVisibility;
use App\Cv\CvSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCareerCvRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'template_version_id' => ['required', 'integer'],
            'custom_headline' => ['nullable', 'string', 'max:255'],
            'custom_summary' => ['nullable', 'string', 'max:3000'],
            'field_visibility' => ['sometimes', 'array:'.implode(',', CvFieldVisibility::keys()), 'size:6'],
            'field_visibility.photo' => ['required_with:field_visibility', 'boolean'],
            'field_visibility.city' => ['required_with:field_visibility', 'boolean'],
            'field_visibility.email' => ['required_with:field_visibility', 'boolean'],
            'field_visibility.whatsapp' => ['required_with:field_visibility', 'boolean'],
            'field_visibility.linkedin_url' => ['required_with:field_visibility', 'boolean'],
            'field_visibility.portfolio_url' => ['required_with:field_visibility', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'active'])],
            'sections' => ['required', 'array', 'size:'.count(CvSection::cases())],
            'sections.*.key' => ['required', 'distinct', Rule::in(CvSection::values())],
            'sections.*.enabled' => ['required', 'boolean'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'sections.*.display_title' => ['nullable', 'string', 'max:120'],
            'sections.*.items' => ['present', 'array'],
            'sections.*.items.*.source_item_id' => ['required', 'integer', 'min:1'],
            'sections.*.items.*.enabled' => ['required', 'boolean'],
            'sections.*.items.*.sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
