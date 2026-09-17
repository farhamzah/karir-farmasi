<?php

namespace App\Http\Requests;

use App\Profile\ProfileSection;
use App\Profile\ProfileSectionRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertProfileSectionRequest extends FormRequest
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
    public function rules(ProfileSectionRegistry $registry): array
    {
        $section = ProfileSection::tryFrom((string) $this->route('section'));
        abort_if($section === null, 404);

        return $registry->rules($section);
    }
}
