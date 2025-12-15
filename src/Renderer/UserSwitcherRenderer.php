<?php

declare(strict_types=1);

namespace Cdoebler\GenericUserSwitcher\Renderer;

use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\RendererInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserProviderInterface;

readonly class UserSwitcherRenderer implements RendererInterface
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private ImpersonatorInterface $impersonator,
    ) {}

    /**
     * @param array{
     *     position?: 'bottom-right'|'bottom-left'|'top-right'|'top-left',
     *     z_index?: int,
     *     param_name?: string,
     *     current_user_id?: int|string
     * } $config
     */
    public function render(array $config = []): string
    {
        $users = $this->userProvider->getUsers();
        if ($users === []) {
            return '';
        }

        $position = $config['position'] ?? 'bottom-right';
        $zIndex = is_int($config['z_index'] ?? null) ? $config['z_index'] : 9999;
        $paramName = $config['param_name'] ?? '_switch_user';

        $positionCss = match ($position) {
            'bottom-left' => 'bottom: 20px; left: 20px;',
            'top-right' => 'top: 20px; right: 20px;',
            'top-left' => 'top: 20px; left: 20px;',
            default => 'bottom: 20px; right: 20px;',
        };

        $positionEscaped = htmlspecialchars($position, ENT_QUOTES | ENT_HTML5);

        $currentUserId = $config['current_user_id'] ?? $this->impersonator->getOriginalUserId();
        $isImpersonating = $this->impersonator->isImpersonating();
        $buttonText = $isImpersonating ? 'Stop Impersonating' : 'Switch User';
        $buttonClass = $isImpersonating ? 'cdoebler-gus-active' : '';

        $userListHtml = '';
        foreach ($users as $user) {
            $userListHtml    .= $this->renderUserItem($user, $paramName, $currentUserId);
        }

        if ($isImpersonating) {
            $userListHtml = $this->renderStopItem($paramName) . $userListHtml;
        }

        return <<<HTML
<style>
    .cdoebler-gus-container {
        position: fixed;
        {$positionCss}
        z-index: {$zIndex};
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .cdoebler-gus-toggle {
        background: #333;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cdoebler-gus-toggle:hover {
        background: #444;
    }
    .cdoebler-gus-toggle.cdoebler-gus-active {
        background: #d32f2f;
    }
    .cdoebler-gus-list {
        display: none;
        position: absolute;
        bottom: 50px; /* Adjust based on position if needed, simpler to just flip via JS or CSS logic but fixed for now */
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        width: 250px;
        max-height: 400px;
        overflow-y: auto;
        padding: 10px 0;
        list-style: none;
        margin: 0;
    }
    .cdoebler-gus-container[data-pos="top-right"] .cdoebler-gus-list, .cdoebler-gus-container[data-pos="top-left"] .cdoebler-gus-list {
        bottom: auto;
        top: 50px;
    }
    .cdoebler-gus-container[data-pos="bottom-left"] .cdoebler-gus-list, .cdoebler-gus-container[data-pos="top-left"] .cdoebler-gus-list {
        right: auto;
        left: 0;
    }
    .cdoebler-gus-list.cdoebler-gus-open {
        display: block;
    }
    .cdoebler-gus-item {
        padding: 8px 15px;
        border-bottom: 1px solid #eee;
    }
    .cdoebler-gus-item-active {
        background: #a3bbe6;
    }
    .cdoebler-gus-item:last-child {
        border-bottom: none;
    }
    .cdoebler-gus-item:hover {
        background: #f8f9fa;
    }
    .cdoebler-gus-link {
        text-decoration: none;
        color: #333;
        display: block;
        font-size: 14px;
    }
    .cdoebler-gus-link:hover {
        color: #007bff;
    }
    .cdoebler-gus-id {
        font-size: 12px;
        color: #888;
        margin-left: 5px;
    }
    .cdoebler-gus-search {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .cdoebler-gus-search input {
        width: 100%;
        padding: 6px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
</style>

<div class="cdoebler-gus-container" data-pos="{$positionEscaped}">
    <ul class="cdoebler-gus-list" id="cdoebler-gus-list">
        <li class="cdoebler-gus-search">
            <input type="text" placeholder="Search users..." onkeyup="gusFilterUsers(this)">
        </li>
        {$userListHtml}
    </ul>
    <button class="cdoebler-gus-toggle {$buttonClass}" onclick="document.getElementById('cdoebler-gus-list').classList.toggle('cdoebler-gus-open')">
        {$buttonText}
    </button>
</div>

<script>
function gusFilterUsers(input) {
    const filter = input.value.toLowerCase();
    const items = document.querySelectorAll('.cdoebler-gus-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(filter) ? '' : 'none';
    });
}
function gusSwitchUser(paramName, id) {
    const url = new URL(window.location.href);
    url.searchParams.set(paramName, id);
    window.location.href = url.toString();
}
function gusStopImpersonating(paramName) {
    const url = new URL(window.location.href);
    url.searchParams.set(paramName, '_stop'); // Special value to stop
    window.location.href = url.toString();
}
</script>
HTML;
    }

    private function renderUserItem(UserInterface $user, string $paramName, int|string|null $currentUserId = null): string
    {
        $id = $user->getIdentifier();
        $name = htmlspecialchars($user->getDisplayName(), ENT_QUOTES | ENT_HTML5);
        $idStr = htmlspecialchars((string)$id, ENT_QUOTES | ENT_HTML5);
        $paramNameEscaped = htmlspecialchars($paramName, ENT_QUOTES | ENT_HTML5);

        $activeUserClass = (string)$currentUserId === (string)$id ? 'cdoebler-gus-item-active' : '';

        return <<<HTML
        <li class="cdoebler-gus-item {$activeUserClass}">
            <a href="javascript:void(0)" data-param-name="{$paramNameEscaped}" data-user-id="{$idStr}" onclick="gusSwitchUser(this.dataset.paramName, this.dataset.userId)" class="cdoebler-gus-link">
                {$name} <span class="cdoebler-gus-id">#{$idStr}</span>
            </a>
        </li>
HTML;
    }

    private function renderStopItem(string $paramName): string
    {
        $paramNameEscaped = htmlspecialchars($paramName, ENT_QUOTES | ENT_HTML5);

        return <<<HTML
        <li class="cdoebler-gus-item" style="background: #fff0f0;">
            <a href="javascript:void(0)" data-param-name="{$paramNameEscaped}" onclick="gusStopImpersonating(this.dataset.paramName)" class="cdoebler-gus-link" style="color: #d32f2f;">
                <strong>Stop Impersonating</strong>
            </a>
        </li>
HTML;
    }
}
