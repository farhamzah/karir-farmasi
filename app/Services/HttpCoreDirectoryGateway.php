<?php

namespace App\Services;

use App\Contracts\CoreDirectoryGateway;
use App\Data\CoreDirectoryPerson;
use App\Data\CoreStudyProgram;
use App\Exceptions\CoreDirectoryUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class HttpCoreDirectoryGateway implements CoreDirectoryGateway
{
    public function __construct(
        private readonly bool $enabled,
        private readonly ?string $baseUrl,
        private readonly ?string $clientId,
        #[\SensitiveParameter] private readonly ?string $clientSecret,
        private readonly array $allowedProgramIds,
        private readonly int $connectTimeoutSeconds,
        private readonly int $timeoutSeconds,
        private readonly string $environment = 'production',
    ) {}

    public function findPerson(string $coreUserId): CoreDirectoryPerson
    {
        $data = $this->get('/people/'.rawurlencode($coreUserId));

        return new CoreDirectoryPerson(
            coreUserId: $this->requiredString($data, 'core_user_id'),
            displayName: $this->requiredString($data, 'display_name'),
        );
    }

    public function findStudyProgram(string $programId): CoreStudyProgram
    {
        if (! in_array($programId, $this->allowedProgramIds, true)) {
            throw new CoreDirectoryUnavailable('Study program is outside the configured scope.');
        }

        return $this->program($this->get('/study-programs/'.rawurlencode($programId)));
    }

    public function listScopedPrograms(): array
    {
        $data = $this->get('/study-programs');
        $items = $data['items'] ?? null;
        if (! is_array($items)) {
            throw new CoreDirectoryUnavailable('Core directory returned a malformed payload.');
        }

        $programs = array_map(fn (mixed $item): CoreStudyProgram => $this->program($item), $items);

        return array_values(array_filter(
            $programs,
            fn (CoreStudyProgram $program): bool => in_array($program->id, $this->allowedProgramIds, true),
        ));
    }

    /** @return array<string, mixed> */
    private function get(string $path): array
    {
        $this->assertConfigured();

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Core-Client-Id' => $this->clientId,
                    'X-Core-Client-Secret' => $this->clientSecret,
                    'X-Core-App-Code' => config('core_identity.app_code'),
                ])
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->withOptions(['allow_redirects' => false])
                ->get(rtrim($this->baseUrl, '/').$path);
        } catch (ConnectionException) {
            throw new CoreDirectoryUnavailable('Core directory is temporarily unavailable.');
        }

        return $this->data($response);
    }

    /** @return array<string, mixed> */
    private function data(Response $response): array
    {
        if (! $response->successful() || $response->redirect()) {
            throw new CoreDirectoryUnavailable('Core directory request failed.');
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new CoreDirectoryUnavailable('Core directory returned a malformed payload.');
        }

        return $data;
    }

    private function program(mixed $data): CoreStudyProgram
    {
        if (! is_array($data)) {
            throw new CoreDirectoryUnavailable('Core directory returned a malformed program.');
        }

        return new CoreStudyProgram(
            id: $this->requiredString($data, 'id'),
            code: $this->requiredString($data, 'code'),
            name: $this->requiredString($data, 'name'),
        );
    }

    /** @param array<string, mixed> $data */
    private function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new CoreDirectoryUnavailable('Core directory returned a malformed payload.');
        }

        return $value;
    }

    private function assertConfigured(): void
    {
        $scheme = is_string($this->baseUrl) ? parse_url($this->baseUrl, PHP_URL_SCHEME) : null;
        $host = is_string($this->baseUrl) ? parse_url($this->baseUrl, PHP_URL_HOST) : null;
        $localHttp = $scheme === 'http'
            && in_array($this->environment, ['local', 'testing'], true)
            && in_array($host, ['127.0.0.1', 'localhost'], true);

        if (! $this->enabled || ($scheme !== 'https' && ! $localHttp) || ! is_string($this->clientId) || $this->clientId === '' || ! is_string($this->clientSecret) || $this->clientSecret === '' || $this->allowedProgramIds === []) {
            throw new CoreDirectoryUnavailable('Core directory HTTP adapter is not configured.');
        }
    }
}
