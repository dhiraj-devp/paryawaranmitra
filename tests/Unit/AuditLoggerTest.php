<?php

namespace Tests\Unit;

use App\Services\Audit\AuditLogger;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    public function test_secret_fields_are_redacted_before_they_can_be_stored(): void
    {
        $clean = app(AuditLogger::class)->redact([
            'password' => 'secret',
            'remember_token' => 'token',
            'note' => 'kept',
        ]);

        $this->assertSame('[redacted]', $clean['password']);
        $this->assertSame('[redacted]', $clean['remember_token']);
        $this->assertSame('kept', $clean['note']);
    }
}
