<?php

namespace Database\Seeders;

use App\Models\CareerEvent;
use App\Models\CareerEventCertificate;
use App\Models\CareerEventRegistration;
use App\Models\CareerEventTopic;
use App\Models\CareerJob;
use App\Models\CareerProfile;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CvTemplateVersion;
use App\Models\LeadershipAssignment;
use App\Talent\TalentIndexBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoTestAccountSeeder extends Seeder
{
    public const TEST_PASSWORD = 'TestPass123!';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $anisa = $this->profile([
            'core_user_id' => 'fixture-core-user-001',
            'alumni_number' => '2210631210002',
            'graduation_year' => 2026,
            'visible_in_alumni_directory' => true,
            'professional_name' => 'Anisa Susanti, S.Farm.',
            'headline' => 'Fresh graduate Farmasi UBP | Minat QA/QC, Regulatory, dan Pelayanan Kefarmasian',
            'professional_summary' => 'Alumni Farmasi Universitas Buana Perjuangan Karawang yang teliti, adaptif, dan siap berkontribusi pada layanan kefarmasian, industri farmasi, serta edukasi kesehatan masyarakat.',
            'professional_email' => 'anisa.susanti@fixture.invalid',
            'whatsapp' => '0812 3456 7890',
            'city' => 'Karawang',
            'linkedin_url' => 'https://linkedin.com/in/anisa-susanti-fixture',
            'portfolio_url' => 'https://portfolio.fixture.invalid/anisa-susanti',
            'open_to_work' => true,
            'profile_visibility' => 'portfolio',
            'discoverable_by_verified_companies' => true,
            'discoverable_by_internal_leadership' => true,
        ]);
        $this->fillProfile($anisa, 'S1 Farmasi', 2026, ['CPOB', 'QA/QC', 'Regulatory', 'Halal', 'Apotek']);

        $this->fillProfile($this->profile([
            'core_user_id' => 'fixture-core-user-002',
            'alumni_number' => '2110631210015',
            'graduation_year' => 2025,
            'visible_in_alumni_directory' => true,
            'professional_name' => 'Nadira Azzahra, S.Farm.',
            'headline' => 'Apoteker muda dengan fokus rumah sakit dan pelayanan pasien',
            'professional_summary' => 'Berpengalaman dalam PKPA rumah sakit, konseling obat, dan edukasi pasien.',
            'professional_email' => 'nadira.azzahra@fixture.invalid',
            'city' => 'Karawang',
            'open_to_work' => true,
            'profile_visibility' => 'portfolio',
            'discoverable_by_verified_companies' => true,
            'discoverable_by_internal_leadership' => true,
        ]), 'Profesi Apoteker', 2025, ['RS', 'PIO', 'Farmakovigilans', 'Patient Safety']);

        $this->fillProfile($this->profile([
            'core_user_id' => 'fixture-core-user-003',
            'alumni_number' => '2010631210008',
            'graduation_year' => 2024,
            'visible_in_alumni_directory' => true,
            'professional_name' => 'Rizky Maulana, S.Farm.',
            'headline' => 'Alumni Farmasi UBP dengan minat produksi dan PBF',
            'professional_summary' => 'Memiliki ketertarikan pada distribusi farmasi, produksi, ISO, dan sistem mutu.',
            'professional_email' => 'rizky.maulana@fixture.invalid',
            'city' => 'Bekasi',
            'open_to_work' => true,
            'profile_visibility' => 'portfolio',
            'discoverable_by_verified_companies' => true,
            'discoverable_by_internal_leadership' => true,
        ]), 'S1 Farmasi', 2024, ['PBF', 'Produksi', 'ISO', 'CPOB']);

        $this->leadershipAssignments();
        $company = $this->company();
        $this->jobs($company);
        $this->eventFor($anisa);
        app(TalentIndexBuilder::class)->rebuild($anisa);

        CareerProfile::query()
            ->whereIn('core_user_id', ['fixture-core-user-002', 'fixture-core-user-003'])
            ->get()
            ->each(fn (CareerProfile $profile) => app(TalentIndexBuilder::class)->rebuild($profile));
    }

    /** @param array<string, mixed> $data */
    private function profile(array $data): CareerProfile
    {
        return CareerProfile::query()->updateOrCreate(
            ['core_user_id' => $data['core_user_id']],
            $data + [
                'last_confirmed_at' => now(),
                'discoverability_updated_at' => now(),
            ],
        );
    }

    /** @param list<string> $terms */
    private function fillProfile(CareerProfile $profile, string $program, int $graduationYear, array $terms): void
    {
        $profile->educations()->updateOrCreate(
            ['institution_name' => 'Universitas Buana Perjuangan Karawang', 'program_name' => $program],
            ['degree' => $program === 'Profesi Apoteker' ? 'Apt.' : 'S.Farm.', 'start_year' => $graduationYear - 4, 'end_year' => $graduationYear, 'status' => 'lulus', 'source' => 'fixture', 'verified_at' => now(), 'sort_order' => 1],
        );

        foreach (array_values($terms) as $index => $term) {
            $profile->skills()->updateOrCreate(
                ['name' => $term],
                ['category' => 'Kompetensi Farmasi', 'level' => $index < 2 ? 'Mahir' : 'Menengah', 'sort_order' => $index + 1],
            );
        }

        $profile->experiences()->updateOrCreate(
            ['organization' => 'Apotek Sehat Farma Karawang', 'title' => 'Asisten Praktik Kefarmasian'],
            ['type' => 'internship', 'location' => 'Karawang', 'start_date' => '2025-01-01', 'end_date' => '2025-06-30', 'description' => 'Membantu pelayanan resep, konseling obat, stok sediaan, dan edukasi penggunaan obat rasional.', 'sort_order' => 1],
        );

        $profile->certifications()->updateOrCreate(
            ['title' => 'Pelatihan Good Pharmacy Practice', 'issuer' => 'Fakultas Farmasi UBP'],
            ['issue_date' => '2025-08-12', 'credential_id' => 'GPP-FIXTURE-'.Str::upper(Str::substr($profile->core_user_id, -3)), 'credential_url' => 'https://certificate.fixture.invalid/gpp', 'sort_order' => 1],
        );

        $profile->projects()->updateOrCreate(
            ['title' => 'Edukasi DAGUSIBU untuk Masyarakat'],
            ['category' => 'Edukasi Kesehatan', 'description' => 'Media edukasi penggunaan dan penyimpanan obat yang aman untuk masyarakat Karawang.', 'project_url' => 'https://portfolio.fixture.invalid/dagusibu', 'sort_order' => 1],
        );

        $profile->languages()->updateOrCreate(
            ['language' => 'Bahasa Indonesia'],
            ['proficiency' => 'Native', 'sort_order' => 1],
        );

        $profile->jobPreference()->updateOrCreate(
            ['career_profile_id' => $profile->id],
            ['target_roles' => ['QA/QC', 'Regulatory Affairs', 'Apoteker', 'Produksi'], 'employment_types' => ['full_time', 'internship'], 'preferred_locations' => ['Karawang', 'Bekasi', 'Jakarta'], 'willing_to_relocate' => true, 'availability_date' => now()->addMonth()->toDateString()],
        );

        $version = CvTemplateVersion::query()->where('status', 'published')->whereNotNull('published_at')->first();
        if ($version !== null) {
            $profile->cvs()->updateOrCreate(
                ['name' => 'CV Utama'],
                ['cv_template_version_id' => $version->id, 'custom_headline' => $profile->headline, 'custom_summary' => $profile->professional_summary, 'status' => 'draft'],
            );
        }
    }

    private function leadershipAssignments(): void
    {
        LeadershipAssignment::query()->updateOrCreate(
            ['actor_core_user_id' => 'fixture-core-dekan-001', 'scope_type' => 'faculty', 'scope_reference' => 'farmasi-ubp'],
            ['scope_label' => 'Fakultas Farmasi UBP', 'program_references' => ['S1 Farmasi', 'Profesi Apoteker'], 'role_label' => 'Dekan', 'active' => true, 'valid_from' => now()->subYear()->toDateString()],
        );

        LeadershipAssignment::query()->updateOrCreate(
            ['actor_core_user_id' => 'fixture-core-kaprodi-001', 'scope_type' => 'program', 'scope_reference' => 'S1 Farmasi'],
            ['scope_label' => 'Program Studi S1 Farmasi', 'program_references' => ['S1 Farmasi'], 'role_label' => 'Kaprodi', 'active' => true, 'valid_from' => now()->subYear()->toDateString()],
        );
    }

    private function company(): Company
    {
        $company = Company::query()->updateOrCreate(
            ['display_name' => 'PT Sehat Farma Karawang'],
            ['legal_name' => 'PT Sehat Farma Karawang', 'business_sector' => 'Industri Farmasi', 'company_size' => '51-200', 'website' => 'https://sehatfarma.fixture.invalid', 'city' => 'Karawang', 'description' => 'Perusahaan farmasi sintetis untuk uji rekrutmen, talent search, lowongan, dan feedback.', 'verification_status' => 'verified', 'verified_at' => now(), 'verified_by_core_user_id' => 'fixture-core-admin-001', 'active' => true],
        );

        CompanyUser::query()->updateOrCreate(
            ['email' => 'recruiter@sehatfarma.fixture.invalid'],
            ['company_id' => $company->id, 'name' => 'Recruiter Sehat Farma', 'password' => self::TEST_PASSWORD, 'role' => 'company_admin', 'active' => true],
        );

        return $company;
    }

    private function jobs(Company $company): void
    {
        $job = CareerJob::query()->updateOrCreate(
            ['employer_display_name' => $company->display_name, 'title' => 'Staff QA/QC Farmasi'],
            ['company_id' => $company->id, 'created_by_type' => 'company', 'created_by_reference' => 'recruiter@sehatfarma.fixture.invalid', 'employment_type' => 'full_time', 'work_mode' => 'onsite', 'city' => 'Karawang', 'location_text' => 'Kawasan Industri Karawang', 'description' => 'Mendukung pengujian mutu, dokumentasi CPOB, dan pemantauan proses produksi farmasi.', 'requirements' => 'S1 Farmasi, memahami CPOB, QA/QC, ISO, dan dokumentasi mutu.', 'responsibilities' => 'Melakukan pemeriksaan mutu bahan dan produk, menyusun laporan, dan bekerja sama dengan tim produksi.', 'education_requirement' => 'S1 Farmasi', 'experience_requirement' => 'Fresh graduate dipersilakan', 'salary_visible' => true, 'salary_min' => 4500000, 'salary_max' => 6500000, 'openings' => 2, 'status' => 'published', 'application_method' => 'internal', 'source_type' => 'company_direct', 'source_name' => $company->display_name, 'published_at' => now(), 'expires_at' => now()->addMonths(2)],
        );

        foreach (['CPOB', 'QA/QC', 'ISO', 'Halal', 'Produksi'] as $tag) {
            $job->tags()->updateOrCreate(
                ['category' => 'skill', 'normalized_label' => Str::of($tag)->lower()->ascii()->toString()],
                ['label' => $tag],
            );
        }
    }

    private function eventFor(CareerProfile $profile): void
    {
        $topic = CareerEventTopic::query()->updateOrCreate(['slug' => 'cpob-qa-qc'], ['label' => 'CPOB dan QA/QC']);
        $event = CareerEvent::query()->updateOrCreate(
            ['slug' => 'bootcamp-cpob-qa-qc-fixture'],
            ['title' => 'Bootcamp CPOB dan QA/QC Farmasi', 'reference' => 'EVT-FIX-001', 'event_type' => 'bootcamp', 'organizer' => 'Fakultas Farmasi UBP', 'description' => 'Kelas intensif portofolio untuk memahami CPOB, QA/QC, dan kesiapan kerja industri farmasi.', 'starts_at' => now()->addWeeks(2), 'ends_at' => now()->addWeeks(2)->addHours(4), 'location_type' => 'onsite', 'location_text' => 'Kampus UBP Karawang', 'capacity' => 80, 'registration_opens_at' => now()->subWeek(), 'registration_closes_at' => now()->addWeek(), 'status' => 'published', 'certificate_enabled' => true, 'created_by_core_user_id' => 'fixture-core-admin-001'],
        );
        $event->topics()->syncWithoutDetaching([$topic->id]);

        $registration = CareerEventRegistration::query()->updateOrCreate(
            ['career_event_id' => $event->id, 'career_profile_id' => $profile->id],
            ['role' => 'participant', 'status' => 'completed', 'registered_at' => now()->subDays(5), 'attended_at' => now()->subDays(2), 'completed_at' => now()->subDays(2)],
        );

        CareerEventCertificate::query()->updateOrCreate(
            ['career_event_registration_id' => $registration->id],
            ['certificate_number' => 'CERT-FIX-ANISA-001', 'verification_code' => 'VERIFY-FIXTURE-ANISA-001', 'issued_at' => now()->subDay(), 'file_path' => 'fixture/certificates/anisa-cpob.pdf', 'file_mime' => 'application/pdf', 'issued_by_core_user_id' => 'fixture-core-admin-001'],
        );
    }
}
