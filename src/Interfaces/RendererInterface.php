<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Interfaces;

interface RendererInterface
{
    /**
     * Render the user switcher widget.
     *
     * @param array{
     *     position?: 'bottom-right'|'bottom-left'|'top-right'|'top-left',
     *     z_index?: int,
     *     param_name?: string
     * } $config
     *
     * @return string The rendered HTML
     */
    public function render(array $config = []): string;
}
