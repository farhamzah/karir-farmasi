<?php

namespace Tests\Feature;

use App\Models\TracerPeriod;
use App\Models\TracerQuestionnaireVersion;
use App\Models\TracerSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class TracerWorkflowTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_submitted_response_is_versioned_and_profile_edits_do_not_rewrite_snapshot(): void
    {
        $profile = $this->profile();
        [$period, $version] = $this->publishedTracer();
        $session = ['core_principal' => $this->principal()];

        $this->withSession($session)->get(route('tracer.show', $period->public_reference))->assertOk();
        $this->withSession($session)->put(route('tracer.save', $period->public_reference), [
            'action' => 'submit',
            'answers' => ['employment_status' => 'Belum diketahui'],
        ])->assertRedirect();

        $submission = TracerSubmission::query()->sole();
        $snapshot = $submission->submission_snapshot;
        $this->assertSame($version->id, $submission->tracer_questionnaire_version_id);
        $this->assertSame('Belum diketahui', $snapshot['answers']['employment_status']);
        $this->assertNull($snapshot['answers']['waiting_months']);
        $profile->update(['city' => 'Bandung']);
        $this->assertSame($snapshot, $submission->fresh()->submission_snapshot);
    }

    public function test_new_version_does_not_rebind_old_submission_and_reopen_is_explicit(): void
    {
        [$period, $version] = $this->publishedTracer();
        $profile = $this->profile();
        $submission = TracerSubmission::query()->create([
            'tracer_period_id' => $period->id,
            'tracer_questionnaire_version_id' => $version->id,
            'career_profile_id' => $profile->id,
            'status' => 'submitted',
            'answers' => ['employment_status' => 'Bekerja'],
            'submission_snapshot' => ['answers' => ['employment_status' => 'Bekerja']],
            'snapshot_checksum' => str_repeat('a', 64),
            'submitted_at' => now(),
        ]);
        $admin = ['core_principal' => $this->principal('admin', ['admin-karir'])];
        $this->withSession($admin)->post(route('admin.tracer.versions.store', $period->public_reference), [
            'questions' => [['id' => 'new_question', 'label' => 'Pertanyaan baru', 'type' => 'text', 'required' => false]],
        ])->assertRedirect();
        $this->assertSame($version->id, $submission->fresh()->tracer_questionnaire_version_id);
        $this->assertSame(2, $period->versions()->max('version'));
        $this->withSession($admin)->put(route('admin.tracer.submissions.reopen', $submission->public_reference))->assertRedirect();
        $this->assertSame('reopened', $submission->fresh()->status);
    }

    /** @return array{TracerPeriod, TracerQuestionnaireVersion} */
    private function publishedTracer(): array
    {
        $period = TracerPeriod::query()->create(['title' => 'Tracer Alumni 2026', 'cohort' => '2022',
            'starts_on' => today()->subDay(), 'ends_on' => today()->addDay(), 'status' => 'published', 'created_by_core_user_id' => 'admin']);
        $version = TracerQuestionnaireVersion::query()->create(['tracer_period_id' => $period->id, 'version' => 1,
            'questions' => [
                ['id' => 'employment_status', 'label' => 'Status saat ini', 'type' => 'select', 'required' => true, 'options' => ['Bekerja', 'Belum bekerja', 'Belum diketahui']],
                ['id' => 'waiting_months', 'label' => 'Masa tunggu', 'type' => 'number', 'required' => false],
            ], 'published_at' => now(), 'created_by_core_user_id' => 'admin']);

        return [$period, $version];
    }
}
