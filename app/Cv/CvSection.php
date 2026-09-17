<?php

namespace App\Cv;

enum CvSection: string
{
    case Summary = 'summary';
    case Education = 'education';
    case Experience = 'experience';
    case Skills = 'skills';
    case Certifications = 'certifications';
    case Organizations = 'organizations';
    case Projects = 'projects';
    case Publications = 'publications';
    case Languages = 'languages';
    case Preferences = 'preferences';
    case Events = 'events';
    case EventCertificates = 'event_certificates';

    public function label(): string
    {
        return match ($this) {
            self::Summary => 'Ringkasan Profesional', self::Education => 'Pendidikan', self::Experience => 'Pengalaman',
            self::Skills => 'Keterampilan', self::Certifications => 'Sertifikasi', self::Organizations => 'Organisasi',
            self::Projects => 'Proyek & Karya', self::Publications => 'Publikasi', self::Languages => 'Bahasa',
            self::Preferences => 'Preferensi Karier', self::Events => 'Seminar & Event', self::EventCertificates => 'Sertifikat Event',
        };
    }

    public function relation(): ?string
    {
        return match ($this) {
            self::Summary => null, self::Education => 'educations', self::Experience => 'experiences', self::Skills => 'skills',
            self::Certifications => 'certifications', self::Organizations => 'organizations', self::Projects => 'projects',
            self::Publications => 'publications', self::Languages => 'languages', self::Preferences => 'jobPreference',
            self::Events => 'completedEventRegistrations', self::EventCertificates => 'eventCertificates',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $section) => $section->value, self::cases());
    }
}
