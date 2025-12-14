<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Generic;

use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;

final readonly class GenericUser implements UserInterface
{
    public function __construct(
        private string|int $identifier,
        private string $displayName,
    ) {}

    public function getIdentifier(): string|int
    {
        return $this->identifier;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
