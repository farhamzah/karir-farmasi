<?php

namespace App\Support;

use LogicException;

final class EnvironmentSafetyGuard
{
    private const ALLOWED_DATABASES = [
        'local' => ['safa_karir_dev'],
        'testing' => ['safa_karir_test'],
    ];

    public static function assertIdentityDriver(string $environment, string $driver): void
    {
        if ($driver === 'fixture' && ! in_array($environment, ['local', 'testing'], true)) {
            throw new LogicException("Fixture identity is forbidden in [$environment].");
        }

        if (! in_array($driver, ['unavailable', 'fixture', 'http'], true)) {
            throw new LogicException("Unknown Core identity driver [$driver].");
        }
    }

    public static function assertDatabase(string $environment, string $connection, string $database): void
    {
        if ($connection !== 'mysql') {
            throw new LogicException("Database connection [$connection] is forbidden; MySQL is required.");
        }

        $allowed = self::ALLOWED_DATABASES[$environment] ?? [];

        if (! in_array($database, $allowed, true)) {
            throw new LogicException("Database [$database] is outside the [$environment] allowlist.");
        }
    }
}
