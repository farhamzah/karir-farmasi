<?php

namespace App\Http\Requests;

use App\Cv\CvTemplateConfiguration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCvTemplateDraftRequest extends FormRequest
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
        $allowed = CvTemplateConfiguration::allowed();

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1000'],
            'configuration' => ['required', 'array:'.implode(',', array_keys($allowed))],
            'configuration.layout' => ['required', Rule::in($allowed['layout'])],
            'configuration.photo' => ['required', Rule::in($allowed['photo'])],
            'configuration.typography' => ['required', Rule::in($allowed['typography'])],
            'configuration.spacing' => ['required', Rule::in($allowed['spacing'])],
            'configuration.header_style' => ['required', Rule::in($allowed['header_style'])],
            'configuration.section_style' => ['required', Rule::in($allowed['section_style'])],
            'configuration.accent' => ['required', Rule::in($allowed['accent'])],
            'configuration.page_padding' => ['required', Rule::in($allowed['page_padding'])],
        ];
    }
}
