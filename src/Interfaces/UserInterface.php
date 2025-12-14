<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Interfaces;

interface UserInterface
{
    public function getIdentifier(): string|int;

    public function getDisplayName(): string;
}
