<?php

use Cdoebler\GenericUserSwitcher\Renderer\UserSwitcherRenderer;
use Cdoebler\GenericUserSwitcher\Generic\InMemoryUserProvider;
use Cdoebler\GenericUserSwitcher\Generic\GenericUser;
use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;

test('it returns empty string if no users', function (): void {
    $provider = new InMemoryUserProvider([]);
    $impersonator = Mockery::mock(ImpersonatorInterface::class);

    $renderer = new UserSwitcherRenderer($provider, $impersonator);

    expect($renderer->render())->toBe('');
});

test('it renders user list', function (): void {
    $users = [new GenericUser(1, 'Alice')];
    $provider = new InMemoryUserProvider($users);

    $impersonator = Mockery::mock(ImpersonatorInterface::class);
    $impersonator->expects('getOriginalUserId')->andReturn(999); // any int that is not Alice's id is fine
    $impersonator->expects('isImpersonating')->andReturn(false);

    $renderer = new UserSwitcherRenderer($provider, $impersonator);
    $html = $renderer->render();

    expect($html)->toContain('Alice')
                 ->toContain('Switch User')
                 ->toContain('gus-container');
});

test('it renders stop button when impersonating', function (): void {
    $users = [new GenericUser(1, 'Alice')];
    $provider = new InMemoryUserProvider($users);

    $impersonator = Mockery::mock(ImpersonatorInterface::class);
    $impersonator->expects('getOriginalUserId')->andReturn(1);
    $impersonator->expects('isImpersonating')->andReturn(true);

    $renderer = new UserSwitcherRenderer($provider, $impersonator);
    $html = $renderer->render();

    expect($html)->toContain('Stop Impersonating')
                 ->toContain('gus-active');
});

test('it respects position config', function (): void {
    $users = [new GenericUser(1, 'Alice')];
    $provider = new InMemoryUserProvider($users);

    $impersonator = Mockery::mock(ImpersonatorInterface::class);
    $impersonator->expects('getOriginalUserId')->andReturn(999);
    $impersonator->expects('isImpersonating')->andReturn(false);

    $renderer = new UserSwitcherRenderer($provider, $impersonator);
    $html = $renderer->render(['position' => 'top-left']);

    expect($html)->toContain('top: 20px; left: 20px;');
});

test('it highlights current user when current_user_id is provided', function (): void {
    $users = [
        new GenericUser(1, 'Alice'),
        new GenericUser(2, 'Bob'),
        new GenericUser(3, 'Charlie'),
    ];
    $provider = new InMemoryUserProvider($users);

    $impersonator = Mockery::mock(ImpersonatorInterface::class);
    // getOriginalUserId should not be called when current_user_id is provided
    $impersonator->expects('isImpersonating')->andReturn(true);

    $renderer = new UserSwitcherRenderer($provider, $impersonator);

    // Pass current_user_id = 2 (Bob) to highlight Bob as the currently impersonated user
    $html = $renderer->render(['current_user_id' => 2]);

    // Bob should be highlighted (his li should have the active class)
    expect($html)->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Bob.*<\/li>/s');

    // Alice should NOT be highlighted
    expect($html)->not->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Alice.*<\/li>/s');
});
