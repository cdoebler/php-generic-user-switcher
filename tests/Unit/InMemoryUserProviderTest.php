<?php

use Cdoebler\GenericUserSwitcher\Generic\InMemoryUserProvider;
use Cdoebler\GenericUserSwitcher\Generic\GenericUser;

test('it returns empty array when no users initialized', function (): void {
    $provider = new InMemoryUserProvider();
    expect($provider->getUsers())->toBeEmpty();
});

test('it returns all users', function (): void {
    $users = [
        new GenericUser(1, 'User 1'),
        new GenericUser(2, 'User 2'),
    ];
    $provider = new InMemoryUserProvider($users);

    expect($provider->getUsers())->toHaveCount(2)
        ->and($provider->getUsers()[0])->toBe($users[0]);
});

test('it finds user by id', function (): void {
    $user1 = new GenericUser(1, 'User 1');
    $user2 = new GenericUser('abc', 'User 2');
    $provider = new InMemoryUserProvider([$user1, $user2]);

    expect($provider->findUserById(1))->toBe($user1)
        ->and($provider->findUserById('abc'))->toBe($user2)
        ->and($provider->findUserById(999))->toBeNull();
});
