<?php

namespace App\Profile;

enum ProfileSection: string
{
    case Education = 'education';
    case Experience = 'experience';
    case Skills = 'skills';
    case Certifications = 'certifications';
    case Organizations = 'organizations';
    case Projects = 'projects';
    case Publications = 'publications';
    case Languages = 'languages';
    case Preferences = 'preferences';

    public function label(): string
    {
        return match ($this) {
            self::Education => 'Pendidikan',
            self::Experience => 'Pengalaman',
            self::Skills => 'Keterampilan',
            self::Certifications => 'Sertifikasi',
            self::Organizations => 'Organisasi',
            self::Projects => 'Proyek & Karya',
            self::Publications => 'Publikasi',
            self::Languages => 'Bahasa',
            self::Preferences => 'Preferensi Karier',
        };
    }

    public function relation(): string
    {
        return match ($this) {
            self::Education => 'educations',
            self::Experience => 'experiences',
            self::Skills => 'skills',
            self::Certifications => 'certifications',
            self::Organizations => 'organizations',
            self::Projects => 'projects',
            self::Publications => 'publications',
            self::Languages => 'languages',
            self::Preferences => 'jobPreference',
        };
    }

    public function singular(): bool
    {
        return $this === self::Preferences;
    }

    public function supportsAttachment(): bool
    {
        return in_array($this, [self::Certifications, self::Projects], true);
    }
}
