<?php

namespace Tests\Unit;

use App\Logging\SensitiveContextProcessor;
use PHPUnit\Framework\TestCase;

class SensitiveContextProcessorTest extends TestCase
{
    public function test_sensitive_context_is_redacted_recursively(): void
    {
        $processor = new SensitiveContextProcessor;
        $result = $processor->redact([
            'event' => 'identity_unavailable',
            'authorization' => 'synthetic-sentinel',
            'nested' => ['password' => 'synthetic-sentinel', 'safe' => 'retained'],
            'x-karir-client-secret' => 'synthetic-sentinel',
        ]);

        $this->assertSame('[REDACTED]', $result['authorization']);
        $this->assertSame('[REDACTED]', $result['nested']['password']);
        $this->assertSame('[REDACTED]', $result['x-karir-client-secret']);
        $this->assertSame('retained', $result['nested']['safe']);
    }
}
