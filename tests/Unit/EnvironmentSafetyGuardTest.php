<?php

namespace Tests\Unit;

use App\Support\EnvironmentSafetyGuard;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnvironmentSafetyGuardTest extends TestCase
{
    public function test_known_local_and_testing_configuration_is_allowed(): void
    {
        EnvironmentSafetyGuard::assertIdentityDriver('local', 'fixture');
        EnvironmentSafetyGuard::assertIdentityDriver('testing', 'unavailable');
        EnvironmentSafetyGuard::assertDatabase('local', 'mysql', 'safa_karir_dev');
        EnvironmentSafetyGuard::assertDatabase('testing', 'mysql', 'safa_karir_test');

        $this->addToAssertionCount(4);
    }

    #[DataProvider('unsafeIdentityConfigurations')]
    public function test_fixture_or_unknown_identity_is_rejected(string $environment, string $driver): void
    {
        $this->expectException(LogicException::class);
        EnvironmentSafetyGuard::assertIdentityDriver($environment, $driver);
    }

    public static function unsafeIdentityConfigurations(): array
    {
        return [
            'production fixture' => ['production', 'fixture'],
            'staging fixture' => ['staging', 'fixture'],
            'unknown fixture' => ['preview', 'fixture'],
            'production fake' => ['production', 'fake'],
            'staging fake' => ['staging', 'fake'],
            'unknown driver' => ['local', 'legacy-password'],
        ];
    }

    #[DataProvider('unsafeDatabaseConfigurations')]
    public function test_non_mysql_or_database_outside_allowlist_is_rejected(string $environment, string $connection, string $database): void
    {
        $this->expectException(LogicException::class);
        EnvironmentSafetyGuard::assertDatabase($environment, $connection, $database);
    }

    public static function unsafeDatabaseConfigurations(): array
    {
        return [
            'sqlite fallback' => ['testing', 'sqlite', ':memory:'],
            'Core database' => ['testing', 'mysql', 'core_farmasi'],
            'dev database in tests' => ['testing', 'mysql', 'safa_karir_dev'],
            'unknown environment' => ['preview', 'mysql', 'safa_karir_test'],
        ];
    }
}
