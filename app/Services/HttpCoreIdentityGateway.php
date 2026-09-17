<?php

namespace App\Services;

use App\Contracts\CoreIdentityGateway;
use App\Data\CorePrincipal;
use App\Exceptions\CoreIdentityDenied;
use App\Exceptions\CoreIdentityUnavailable;
use App\Support\CorePrincipalNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class HttpCoreIdentityGateway implements CoreIdentityGateway
{
    public function __construct(
        private readonly CorePrincipalNormalizer $normalizer,
        private readonly bool $enabled,
        private readonly ?string $verifyUrl,
        private readonly ?string $clientId,
        #[\SensitiveParameter] private readonly ?string $clientSecret,
        private readonly int $connectTimeoutSeconds,
        private readonly int $timeoutSeconds,
        private readonly string $environment = 'production',
    ) {}

    public function currentPrincipal(): CorePrincipal
    {
        throw new CoreIdentityUnavailable('A local SAFA KARIR session has no verified Core principal.');
    }

    public function authenticate(string $identifier, #[\SensitiveParameter] string $password): CorePrincipal
    {
        $this->assertConfigured();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Core-Client-Id' => $this->clientId,
                    'X-Core-Client-Secret' => $this->clientSecret,
                    'X-Core-App-Code' => config('core_identity.app_code'),
                ])
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->withOptions(['allow_redirects' => false])
                ->post($this->verifyUrl, [
                    'identifier' => $identifier,
                    'password' => $password,
                ]);
        } catch (ConnectionException) {
            throw new CoreIdentityUnavailable('Core identity verification is temporarily unavailable.');
        }

        if ($response->serverError()) {
            throw new CoreIdentityUnavailable('Core identity verification is temporarily unavailable.');
        }

        if (in_array($response->status(), [401, 403, 404, 422], true) || $response->redirect()) {
            throw new CoreIdentityDenied('Core identity verification denied the request.');
        }

        if (! $response->successful()) {
            throw new CoreIdentityDenied('Core identity verification returned an unsupported response.');
        }

        $payload = $response->json('principal');
        if (! is_array($payload)) {
            throw new CoreIdentityDenied('Core identity verification returned a malformed payload.');
        }

        return $this->normalizer->normalize($payload);
    }

    private function assertConfigured(): void
    {
        $scheme = is_string($this->verifyUrl) ? parse_url($this->verifyUrl, PHP_URL_SCHEME) : null;
        $path = is_string($this->verifyUrl) ? parse_url($this->verifyUrl, PHP_URL_PATH) : null;

        $host = is_string($this->verifyUrl) ? parse_url($this->verifyUrl, PHP_URL_HOST) : null;
        $localHttp = $scheme === 'http'
            && in_array($this->environment, ['local', 'testing'], true)
            && in_array($host, ['127.0.0.1', 'localhost'], true);

        if (! $this->enabled || ($scheme !== 'https' && ! $localHttp) || ! is_string($path) || $path === '') {
            throw new CoreIdentityUnavailable('Core identity HTTP adapter is not configured.');
        }

        if ($path === '/api/v1/auth/login' || ! is_string($this->clientId) || $this->clientId === '' || ! is_string($this->clientSecret) || $this->clientSecret === '') {
            throw new CoreIdentityUnavailable('Core identity HTTP adapter is not configured.');
        }
    }
}
