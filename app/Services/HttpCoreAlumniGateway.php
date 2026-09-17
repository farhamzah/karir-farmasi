<?php

namespace App\Services;

use App\Contracts\CoreAlumniGateway;
use App\Exceptions\CoreAlumniOperationFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class HttpCoreAlumniGateway implements CoreAlumniGateway
{
    public function __construct(
        private readonly bool $enabled,
        private readonly ?string $baseUrl,
        private readonly ?string $clientId,
        #[\SensitiveParameter] private readonly ?string $clientSecret,
        private readonly int $connectTimeoutSeconds,
        private readonly int $timeoutSeconds,
        private readonly string $environment,
    ) {}

    public function register(array $data): array
    {
        return $this->data($this->send('post', '/alumni-registrations', $data));
    }

    public function status(string $reference): array
    {
        return $this->data($this->send('get', '/alumni-registrations/'.rawurlencode($reference).'/status'));
    }

    public function registrations(?string $status = null, int $page = 1): array
    {
        $payload = $this->json($this->send('get', '/alumni-registrations', array_filter([
            'status' => $status,
            'page' => $page,
        ], fn ($value): bool => $value !== null)));

        if (! is_array($payload['data'] ?? null) || ! is_array($payload['meta'] ?? null)) {
            throw new CoreAlumniOperationFailed('Core returned a malformed registration list.', 502);
        }

        return ['data' => $payload['data'], 'meta' => $payload['meta']];
    }

    public function registration(string $reference): array
    {
        return $this->data($this->send('get', '/alumni-registrations/'.rawurlencode($reference)));
    }

    public function approve(string $reference, string $approverCoreUserId): array
    {
        return $this->data($this->send('post', '/alumni-registrations/'.rawurlencode($reference).'/approve', [
            'approver_core_user_id' => $approverCoreUserId,
        ]));
    }

    public function reject(string $reference, string $approverCoreUserId, string $reason): array
    {
        return $this->data($this->send('post', '/alumni-registrations/'.rawurlencode($reference).'/reject', [
            'approver_core_user_id' => $approverCoreUserId,
            'reason' => $reason,
        ]));
    }

    /** @param array<string, mixed> $data */
    private function send(string $method, string $path, array $data = []): Response
    {
        $this->assertConfigured();

        try {
            $request = $this->request();
            $url = rtrim((string) $this->baseUrl, '/').$path;

            return $method === 'get' ? $request->get($url, $data) : $request->post($url, $data);
        } catch (ConnectionException) {
            throw new CoreAlumniOperationFailed('Layanan registrasi Core sementara tidak tersedia.');
        }
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Core-Client-Id' => $this->clientId,
                'X-Core-Client-Secret' => $this->clientSecret,
                'X-Core-App-Code' => config('core_identity.app_code'),
            ])
            ->connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds)
            ->withOptions(['allow_redirects' => false]);
    }

    /** @return array<string, mixed> */
    private function data(Response $response): array
    {
        $payload = $this->json($response);
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            throw new CoreAlumniOperationFailed('Core returned a malformed registration response.', 502);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function json(Response $response): array
    {
        if ($response->serverError()) {
            throw new CoreAlumniOperationFailed('Layanan registrasi Core sementara tidak tersedia.', 503);
        }

        if ($response->redirect() || in_array($response->status(), [401, 403, 404], true)) {
            throw new CoreAlumniOperationFailed('Permintaan registrasi ditolak oleh Core.', $response->status());
        }

        if ($response->unprocessableEntity()) {
            throw new CoreAlumniOperationFailed('Data registrasi belum valid.', 422, (array) $response->json('errors', []));
        }

        if (! $response->successful() || ! is_array($response->json())) {
            throw new CoreAlumniOperationFailed('Core returned an unsupported registration response.', 502);
        }

        return $response->json();
    }

    private function assertConfigured(): void
    {
        $scheme = is_string($this->baseUrl) ? parse_url($this->baseUrl, PHP_URL_SCHEME) : null;
        $host = is_string($this->baseUrl) ? parse_url($this->baseUrl, PHP_URL_HOST) : null;
        $localHttp = $scheme === 'http'
            && in_array($this->environment, ['local', 'testing'], true)
            && in_array($host, ['127.0.0.1', 'localhost'], true);

        if (! $this->enabled || ($scheme !== 'https' && ! $localHttp)
            || blank($this->clientId) || blank($this->clientSecret)) {
            throw new CoreAlumniOperationFailed('Core alumni gateway is not configured.');
        }
    }
}
