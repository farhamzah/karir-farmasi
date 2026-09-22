<?php

namespace App\Profile;

use App\Models\CareerCertification;
use App\Models\CareerEducation;
use App\Models\CareerExperience;
use App\Models\CareerJobPreference;
use App\Models\CareerLanguage;
use App\Models\CareerOrganization;
use App\Models\CareerProject;
use App\Models\CareerPublication;
use App\Models\CareerSkill;
use App\Models\OwnedProfileModel;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

final class ProfileSectionRegistry
{
    /** @return class-string<OwnedProfileModel> */
    public function modelClass(ProfileSection $section): string
    {
        return match ($section) {
            ProfileSection::Education => CareerEducation::class,
            ProfileSection::Experience => CareerExperience::class,
            ProfileSection::Skills => CareerSkill::class,
            ProfileSection::Certifications => CareerCertification::class,
            ProfileSection::Organizations => CareerOrganization::class,
            ProfileSection::Projects => CareerProject::class,
            ProfileSection::Publications => CareerPublication::class,
            ProfileSection::Languages => CareerLanguage::class,
            ProfileSection::Preferences => CareerJobPreference::class,
        };
    }

    /** @return array<string, list<mixed>> */
    public function rules(ProfileSection $section): array
    {
        $common = [
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_visible' => ['nullable', 'boolean'],
        ];

        $rules = match ($section) {
            ProfileSection::Education => [
                'institution_name' => ['required', 'string', 'max:255'],
                'program_name' => ['required', 'string', 'max:255'],
                'degree' => ['nullable', 'string', 'max:100'],
                'gpa' => ['nullable', 'numeric', 'between:0,4', 'decimal:0,2'],
                'start_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
                'end_year' => ['nullable', 'integer', 'min:1950', 'max:2100', 'gte:start_year'],
                'status' => ['nullable', Rule::in(['studying', 'graduated', 'incomplete'])],
            ],
            ProfileSection::Experience => [
                'type' => ['required', Rule::in(['work', 'internship', 'pkpa', 'kp', 'volunteer', 'other'])],
                'organization' => ['required', 'string', 'max:255'],
                'title' => ['required', 'string', 'max:255'],
                'location' => ['nullable', 'string', 'max:255'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'currently_active' => ['nullable', 'boolean'],
                'description' => ['nullable', 'string', 'max:5000'],
            ],
            ProfileSection::Skills => [
                'name' => ['required', 'string', 'max:120'],
                'category' => ['nullable', 'string', 'max:120'],
                'level' => ['nullable', 'string', 'max:50'],
            ],
            ProfileSection::Certifications => [
                'title' => ['required', 'string', 'max:255'],
                'issuer' => ['required', 'string', 'max:255'],
                'issue_date' => ['nullable', 'date'],
                'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
                'credential_id' => ['nullable', 'string', 'max:255'],
                'credential_url' => ['nullable', 'url:http,https', 'max:2048'],
                'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
            ],
            ProfileSection::Organizations => [
                'organization' => ['required', 'string', 'max:255'],
                'role' => ['required', 'string', 'max:255'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'description' => ['nullable', 'string', 'max:5000'],
            ],
            ProfileSection::Projects => [
                'title' => ['required', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:120'],
                'description' => ['nullable', 'string', 'max:5000'],
                'project_url' => ['nullable', 'url:http,https', 'max:2048'],
                'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
            ],
            ProfileSection::Publications => [
                'title' => ['required', 'string', 'max:255'],
                'publication_name' => ['nullable', 'string', 'max:255'],
                'published_on' => ['nullable', 'date'],
                'url' => ['nullable', 'url:http,https', 'max:2048'],
                'doi' => ['nullable', 'string', 'max:255'],
            ],
            ProfileSection::Languages => [
                'language' => ['required', 'string', 'max:120'],
                'proficiency' => ['nullable', 'string', 'max:120'],
            ],
            ProfileSection::Preferences => [
                'target_roles' => ['nullable', 'string', 'max:2000'],
                'employment_types' => ['nullable', 'string', 'max:2000'],
                'preferred_locations' => ['nullable', 'string', 'max:2000'],
                'willing_to_relocate' => ['nullable', 'boolean'],
                'availability_date' => ['nullable', 'date'],
            ],
        };

        return $section === ProfileSection::Preferences ? $rules : [...$rules, ...$common];
    }

    /** @return list<string> */
    public function writableFields(ProfileSection $section): array
    {
        return array_values(array_filter(
            array_keys($this->rules($section)),
            fn (string $field): bool => $field !== 'attachment',
        ));
    }

    /** @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public function normalize(ProfileSection $section, array $validated): array
    {
        $data = array_intersect_key($validated, array_flip($this->writableFields($section)));

        if ($section === ProfileSection::Education) {
            $data['source'] = 'user_declared';
            $data['source_reference'] = null;
            $data['verified_at'] = null;
        }

        if ($section === ProfileSection::Preferences) {
            foreach (['target_roles', 'employment_types', 'preferred_locations'] as $field) {
                $value = $data[$field] ?? '';
                $data[$field] = array_values(array_filter(array_map('trim', explode(',', (string) $value))));
            }
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function serialize(ProfileSection $section, Model $record): array
    {
        $data = ['id' => $record->getKey()];

        foreach ($this->writableFields($section) as $field) {
            $value = $record->getAttribute($field);
            $data[$field] = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
        }

        if ($section === ProfileSection::Education) {
            $data['source'] = $record->getAttribute('source');
            $data['verified'] = $record->getAttribute('source') === 'core_verified'
                && $record->getAttribute('verified_at') !== null;
        }

        if ($section->supportsAttachment()) {
            $data['attachment_available'] = $record->getAttribute('attachment_path') !== null;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function uiDefinition(ProfileSection $section): array
    {
        return match ($section) {
            ProfileSection::Education => ['empty' => 'Belum ada pendidikan', 'fields' => [
                ['name' => 'institution_name', 'label' => 'Institusi', 'type' => 'text', 'required' => true],
                ['name' => 'program_name', 'label' => 'Program studi', 'type' => 'text', 'required' => true],
                ['name' => 'degree', 'label' => 'Jenjang', 'type' => 'text'],
                ['name' => 'gpa', 'label' => 'IPK (opsional, skala 4,00)', 'type' => 'number', 'min' => 0, 'max' => 4, 'step' => '0.01'],
                ['name' => 'start_year', 'label' => 'Tahun mulai', 'type' => 'number'],
                ['name' => 'end_year', 'label' => 'Tahun selesai', 'type' => 'number'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['studying', 'graduated', 'incomplete']],
            ]],
            ProfileSection::Experience => ['empty' => 'Belum ada pengalaman kerja', 'fields' => [
                ['name' => 'type', 'label' => 'Jenis pengalaman', 'type' => 'select', 'required' => true, 'options' => ['work', 'internship', 'pkpa', 'kp', 'volunteer', 'other']],
                ['name' => 'organization', 'label' => 'Organisasi/perusahaan', 'type' => 'text', 'required' => true],
                ['name' => 'title', 'label' => 'Peran/jabatan', 'type' => 'text', 'required' => true],
                ['name' => 'location', 'label' => 'Lokasi', 'type' => 'text'],
                ['name' => 'start_date', 'label' => 'Mulai', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'Selesai', 'type' => 'date'],
                ['name' => 'currently_active', 'label' => 'Masih aktif', 'type' => 'checkbox'],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ]],
            ProfileSection::Skills => ['empty' => 'Belum ada keterampilan', 'fields' => [
                ['name' => 'name', 'label' => 'Keterampilan', 'type' => 'text', 'required' => true],
                ['name' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                ['name' => 'level', 'label' => 'Level (opsional)', 'type' => 'text'],
            ]],
            ProfileSection::Certifications => ['empty' => 'Belum ada sertifikasi', 'fields' => [
                ['name' => 'title', 'label' => 'Sertifikasi', 'type' => 'text', 'required' => true],
                ['name' => 'issuer', 'label' => 'Penerbit', 'type' => 'text', 'required' => true],
                ['name' => 'issue_date', 'label' => 'Tanggal terbit', 'type' => 'date'],
                ['name' => 'expiry_date', 'label' => 'Tanggal kedaluwarsa', 'type' => 'date'],
                ['name' => 'credential_id', 'label' => 'ID kredensial', 'type' => 'text'],
                ['name' => 'credential_url', 'label' => 'URL kredensial', 'type' => 'url'],
                ['name' => 'attachment', 'label' => 'Lampiran privat (PDF/JPG/PNG)', 'type' => 'file'],
            ]],
            ProfileSection::Organizations => ['empty' => 'Belum ada pengalaman organisasi', 'fields' => [
                ['name' => 'organization', 'label' => 'Organisasi', 'type' => 'text', 'required' => true],
                ['name' => 'role', 'label' => 'Peran', 'type' => 'text', 'required' => true],
                ['name' => 'start_date', 'label' => 'Mulai', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'Selesai', 'type' => 'date'],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ]],
            ProfileSection::Projects => ['empty' => 'Belum ada proyek atau karya', 'fields' => [
                ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                ['name' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ['name' => 'project_url', 'label' => 'URL proyek', 'type' => 'url'],
                ['name' => 'attachment', 'label' => 'Lampiran privat (PDF/JPG/PNG)', 'type' => 'file'],
            ]],
            ProfileSection::Publications => ['empty' => 'Belum ada publikasi', 'fields' => [
                ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                ['name' => 'publication_name', 'label' => 'Nama publikasi', 'type' => 'text'],
                ['name' => 'published_on', 'label' => 'Tanggal terbit', 'type' => 'date'],
                ['name' => 'url', 'label' => 'URL', 'type' => 'url'],
                ['name' => 'doi', 'label' => 'DOI', 'type' => 'text'],
            ]],
            ProfileSection::Languages => ['empty' => 'Belum ada bahasa', 'fields' => [
                ['name' => 'language', 'label' => 'Bahasa', 'type' => 'text', 'required' => true],
                ['name' => 'proficiency', 'label' => 'Kemahiran (deklarasi pengguna)', 'type' => 'text'],
            ]],
            ProfileSection::Preferences => ['empty' => 'Preferensi karier belum diisi', 'fields' => [
                ['name' => 'target_roles', 'label' => 'Target posisi (pisahkan koma)', 'type' => 'text'],
                ['name' => 'employment_types', 'label' => 'Jenis pekerjaan (pisahkan koma)', 'type' => 'text'],
                ['name' => 'preferred_locations', 'label' => 'Lokasi pilihan (pisahkan koma)', 'type' => 'text'],
                ['name' => 'willing_to_relocate', 'label' => 'Bersedia relokasi', 'type' => 'checkbox'],
                ['name' => 'availability_date', 'label' => 'Tanggal tersedia', 'type' => 'date'],
            ]],
        };
    }
}
