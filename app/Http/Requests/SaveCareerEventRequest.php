<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveCareerEventRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'in:seminar,workshop,webinar,pelatihan,bootcamp,kuliah_tamu,career_event'],
            'organizer' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'location_type' => ['required', 'in:online,onsite,hybrid'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'certificate_enabled' => ['required', 'boolean'],
            'topics' => ['required', 'array', 'min:1', 'max:12'],
            'topics.*' => ['required', 'string', 'max:100', 'distinct'],
        ];
    }
}
