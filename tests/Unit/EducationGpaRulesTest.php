<?php

namespace Tests\Unit;

use App\Profile\ProfileSection;
use App\Profile\ProfileSectionRegistry;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EducationGpaRulesTest extends TestCase
{
    public function test_gpa_is_optional_and_limited_to_two_decimals_on_a_four_point_scale(): void
    {
        $rules = app(ProfileSectionRegistry::class)->rules(ProfileSection::Education);
        $education = ['institution_name' => 'Universitas Sintetis', 'program_name' => 'Farmasi'];

        foreach ([null, '0', '3.78', '4.00'] as $gpa) {
            $this->assertTrue(Validator::make([...$education, 'gpa' => $gpa], $rules)->passes());
        }

        foreach (['-0.01', '4.01', '3.789', 'bukan angka'] as $gpa) {
            $this->assertTrue(Validator::make([...$education, 'gpa' => $gpa], $rules)->fails());
        }
    }
}
