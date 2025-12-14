<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Interfaces;

interface ImpersonatorInterface
{
    public function impersonate(string|int $identifier): void;

    public function stopImpersonating(): void;

    public function isImpersonating(): bool;

    public function getOriginalUserId(): string|int|null;
}
