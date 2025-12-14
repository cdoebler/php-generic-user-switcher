<?php

use Cdoebler\GenericUserSwitcher\Generic\GenericUser;

test('it implements UserInterface', function (): void {
    $user = new GenericUser('1', 'John Doe');
    expect($user)->toBeInstanceOf(\Cdoebler\GenericUserSwitcher\Interfaces\UserInterface::class);
});

test('it returns correct identifier and display name', function (): void {
    $user = new GenericUser(123, 'Jane Doe');

    expect($user->getIdentifier())->toBe(123)
        ->and($user->getDisplayName())->toBe('Jane Doe');
});
