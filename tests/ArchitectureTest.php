<?php

arch("Code satisfies Pest's php presets")
    ->preset()
    ->php();

arch("Code satisfies Pest's security presets")
    ->preset()
    ->security();

arch('Comparison of models always uses strict equality')
    ->expect('Cdoebler')
    ->toUseStrictEquality();

// Redundant since in Laravel presets as well but mandatory not to forget
test('No debugging statements are left in the code')
    ->expect(['dd', 'dump', 'ray', 'ds', 'print_r', 'var_dump'])
    ->not->toBeUsed();
