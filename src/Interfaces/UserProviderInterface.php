<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Interfaces;

interface UserProviderInterface
{
    /**
     * @return array<UserInterface>
     */
    public function getUsers(): array;

    public function findUserById(string|int $identifier): ?UserInterface;
}
