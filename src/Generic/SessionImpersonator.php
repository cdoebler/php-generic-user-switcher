<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Generic;

use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use RuntimeException;

final readonly class SessionImpersonator implements ImpersonatorInterface
{
    public function __construct(
        private string $sessionKey = 'generic_user_switcher_impersonator',
    ) {
        if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
            session_start();
        }
    }

    public function impersonate(string|int $identifier): void
    {
        $_SESSION[$this->sessionKey] = $identifier;
    }

    public function stopImpersonating(): void
    {
        unset($_SESSION[$this->sessionKey]);
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
