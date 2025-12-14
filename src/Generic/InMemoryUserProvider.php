<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Generic;

use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserProviderInterface;

final class InMemoryUserProvider implements UserProviderInterface
{
    /**
     * @var array<string|int, UserInterface>
     */
    private array $users = [];

    /**
     * @param array<UserInterface> $users
     */
    public function __construct(array $users = [])
    {
        foreach ($users as $user) {
            $this->users[$user->getIdentifier()] = $user;
        }
    }

    public function getUsers(): array
    {
        return array_values($this->users);
    }

    public function findUserById(string|int $identifier): ?UserInterface
    {
        return $this->users[$identifier] ?? null;
    }
}
