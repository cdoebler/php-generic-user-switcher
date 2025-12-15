<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Generic;

use Cdoebler\GenericUserSwitcher\Interfaces\AuditLoggerInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class SessionImpersonator implements ImpersonatorInterface
{
    public function __construct(
        private string $sessionKey = 'generic_user_switcher_impersonator',
        private ?AuditLoggerInterface $auditLogger = null,
    ) {
        if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
            session_start();
        }
    }

    public function impersonate(string|int $identifier): void
    {
        if (is_string($identifier)) {
            $identifier = trim($identifier);

            if ($identifier === '') {
                throw new InvalidArgumentException('User identifier cannot be empty.');
            }

            if (strlen($identifier) > 255) {
                throw new InvalidArgumentException('User identifier cannot exceed 255 characters.');
            }
        }

        $_SESSION[$this->sessionKey] = $identifier;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $this->auditLogger?->logImpersonationStarted($identifier);
    }

    public function stopImpersonating(): void
    {
        $this->auditLogger?->logImpersonationStopped();

        unset($_SESSION[$this->sessionKey]);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function isImpersonating(): bool
    {
        return isset($_SESSION[$this->sessionKey]);
    }

    public function getOriginalUserId(): string|int|null
    {
        $value = $_SESSION[$this->sessionKey] ?? null;

        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_string($value)) {
            return $value;
        }

        throw new RuntimeException(
            sprintf(
                'Unexpected type stored in session for key "%s".',
                $this->sessionKey
            )
        );
    }
}
