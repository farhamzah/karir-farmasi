<?php

namespace App\Logging;

use Monolog\LogRecord;

final class SensitiveContextProcessor
{
    private const SENSITIVE_KEYS = [
        'authorization', 'cookie', 'password', 'password_confirmation',
        'token', 'access_token', 'refresh_token', 'client_secret', 'api_token',
        'x-karir-client-secret',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
