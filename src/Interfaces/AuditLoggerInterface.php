<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Interfaces;

interface AuditLoggerInterface
{
    public function logImpersonationStarted(string|int $targetUserId): void;

    public function logImpersonationStopped(): void;
}
