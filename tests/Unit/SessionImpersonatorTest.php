<?php

use Cdoebler\GenericUserSwitcher\Generic\SessionImpersonator;

beforeEach(function (): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    $_SESSION = [];
});

test('it can verify if impersonating', function (): void {
    $impersonator = new SessionImpersonator();

    expect($impersonator->isImpersonating())->toBeFalse();

    $impersonator->impersonate(123);
    expect($impersonator->isImpersonating())->toBeTrue();
});

test('it can stop impersonating', function (): void {
    $impersonator = new SessionImpersonator();
    $impersonator->impersonate(123);

    expect($impersonator->isImpersonating())->toBeTrue();

    $impersonator->stopImpersonating();
    expect($impersonator->isImpersonating())->toBeFalse();
    expect($impersonator->getOriginalUserId())->toBeNull();
});

test('it returns original user id', function (): void {
    $impersonator = new SessionImpersonator();
    $impersonator->impersonate(456);

    expect($impersonator->getOriginalUserId())->toBe(456);
});

test('it uses configured session key', function (): void {
    $impersonator = new SessionImpersonator('custom_key');
    $impersonator->impersonate(789);

    expect($_SESSION['custom_key'])->toBe(789)
        ->and(isset($_SESSION['generic_user_switcher_impersonator']))->toBeFalse();
});
