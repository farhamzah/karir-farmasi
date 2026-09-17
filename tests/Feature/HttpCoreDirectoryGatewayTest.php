<?php

namespace Tests\Feature;

use App\Exceptions\CoreDirectoryUnavailable;
use App\Services\HttpCoreDirectoryGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpCoreDirectoryGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_directory_responses_are_reduced_to_allowlisted_fields(): void
    {
        Http::fake([
            'https://directory.invalid/people/core-user-001' => Http::response(['data' => [
                'core_user_id' => 'core-user-001',
                'display_name' => 'Alumni Sintetis',
                'password_hash' => 'discard-this-field',
                'address' => 'discard-this-field',
            ]]),
            'https://directory.invalid/study-programs/farmasi-ubp' => Http::response(['data' => [
                'id' => 'farmasi-ubp',
                'code' => 'FAR',
                'name' => 'Farmasi UBP',
                'all_students' => ['discard-this-field'],
            ]]),
            'https://directory.invalid/study-programs' => Http::response(['data' => ['items' => [[
                'id' => 'farmasi-ubp',
                'code' => 'FAR',
                'name' => 'Farmasi UBP',
                'extra' => 'discard-this-field',
            ]]]]),
        ]);

        $gateway = $this->gateway();
        $person = $gateway->findPerson('core-user-001');
        $program = $gateway->findStudyProgram('farmasi-ubp');
        $programs = $gateway->listScopedPrograms();

        $this->assertSame([
            'coreUserId' => 'core-user-001',
            'displayName' => 'Alumni Sintetis',
        ], get_object_vars($person));
        $this->assertSame([
            'id' => 'farmasi-ubp',
            'code' => 'FAR',
            'name' => 'Farmasi UBP',
        ], get_object_vars($program));
        $this->assertCount(1, $programs);
        $this->assertSame('farmasi-ubp', $programs[0]->id);
        Http::assertSentCount(3);
    }

    public function test_directory_rejects_program_outside_local_scope_without_sending_request(): void
    {
        Http::fake();

        try {
            $this->gateway()->findStudyProgram('outside-farmasi');
            $this->fail('An out-of-scope study program was accepted.');
        } catch (CoreDirectoryUnavailable) {
            Http::assertNothingSent();
        }
    }

    private function gateway(): HttpCoreDirectoryGateway
    {
        return new HttpCoreDirectoryGateway(
            enabled: true,
            baseUrl: 'https://directory.invalid',
            clientId: 'synthetic-client',
            clientSecret: str_repeat('s', 24),
            allowedProgramIds: ['farmasi-ubp'],
            connectTimeoutSeconds: 2,
            timeoutSeconds: 5,
        );
    }
}
