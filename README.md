# PHP Generic User Switcher

A framework-agnostic PHP package for user switching with an integrated frontend component. Perfect for development and debugging, allowing seamless impersonation of different users without logging in and out.

## Features

- **Framework-Agnostic**: Works with any PHP application or framework
- **Clean Interface-Based Design**: Easy to extend and customize
- **Session-Based Impersonation**: Non-destructive user switching with preserved original user identity
- **Visual UI Component**: Beautiful, searchable dropdown widget for quick user switching
- **Type-Safe**: Built with PHP 8.2+ strict types and PHPStan level 10 validation
- **Zero Dependencies**: No external production dependencies
- **Fully Tested**: Comprehensive test coverage with Pest

## Requirements

- PHP 8.2 or higher
- Session support (for default `SessionImpersonator`)

## Installation

Install via Composer:

```bash
composer require cdoebler/php-generic-user-switcher
```

## Quick Start

### Basic Usage

```php
<?php

use Cdoebler\GenericUserSwitcher\Generic\GenericUser;
use Cdoebler\GenericUserSwitcher\Generic\InMemoryUserProvider;
use Cdoebler\GenericUserSwitcher\Generic\SessionImpersonator;
use Cdoebler\GenericUserSwitcher\Renderer\UserSwitcherRenderer;

// 1. Create your users
$users = [
    new GenericUser(1, 'John Admin'),
    new GenericUser(2, 'Jane Developer'),
    new GenericUser(3, 'Bob Guest'),
];

// 2. Set up the user provider
$userProvider = new InMemoryUserProvider($users);

// 3. Set up the impersonator
$impersonator = new SessionImpersonator();

// 4. Render the switcher in your layout/template
$renderer = new UserSwitcherRenderer($userProvider, $impersonator);
echo $renderer->render();
```

### Handling User Switching

In your application's bootstrap or middleware, handle the switch request:

```php
<?php

// Handle user switching requests
if (isset($_GET['_switch_user'])) {
    if ($_GET['_switch_user'] === '_stop') {
        $impersonator->stopImpersonating();
    } else {
        $impersonator->impersonate($_GET['_switch_user']);
    }

    // Redirect to remove the parameter from URL
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Get the current user (impersonated or real)
if ($impersonator->isImpersonating()) {
    $currentUserId = $_SESSION['generic_user_switcher_impersonator'];
    $originalUserId = $impersonator->getOriginalUserId();

    // Load the impersonated user
    $currentUser = $userProvider->findUserById($currentUserId);
} else {
    // Load the actual logged-in user
    $currentUser = getCurrentUser(); // Your app's logic
}
```

## Usage Examples

### Example 1: Simple In-Memory Users

```php
<?php

$users = [
    new GenericUser(1, 'Admin User'),
    new GenericUser(2, 'Regular User'),
    new GenericUser(3, 'Guest User'),
];

$provider = new InMemoryUserProvider($users);
$impersonator = new SessionImpersonator();
$renderer = new UserSwitcherRenderer($provider, $impersonator);

// Render with default options (bottom-right position)
echo $renderer->render();
```

### Example 2: Custom Position and Styling

```php
<?php

echo $renderer->render([
    'position' => 'top-left',     // Options: top-left, top-right, bottom-left, bottom-right
    'z_index' => 10000,            // CSS z-index for the widget
    'param_name' => '_su',         // Custom URL parameter name
]);
```

### Example 3: Custom Session Key

```php
<?php

// Use a custom session key for storing impersonation state
$impersonator = new SessionImpersonator('my_custom_session_key');
```

### Example 4: Database User Provider

Create a custom provider that loads users from your database:

```php
<?php

namespace App\UserSwitcher;

use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserProviderInterface;

class DatabaseUserProvider implements UserProviderInterface
{
    public function __construct(
        private \PDO $pdo
    ) {}

    public function getUsers(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM users ORDER BY name');
        $users = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $users[] = new \Cdoebler\GenericUserSwitcher\Generic\GenericUser(
                $row['id'],
                $row['name']
            );
        }

        return $users;
    }

    public function findUserById(string|int $identifier): ?UserInterface
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM users WHERE id = ?');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new \Cdoebler\GenericUserSwitcher\Generic\GenericUser(
            $row['id'],
            $row['name']
        );
    }
}

// Usage
$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'user', 'pass');
$provider = new DatabaseUserProvider($pdo);
$renderer = new UserSwitcherRenderer($provider, $impersonator);
```

### Example 5: Custom User Implementation

Implement the `UserInterface` for your existing user models:

```php
<?php

namespace App\Models;

use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;

class User implements UserInterface
{
    public function __construct(
        private int $id,
        private string $email,
        private string $firstName,
        private string $lastName,
    ) {}

    public function getIdentifier(): string|int
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return "{$this->firstName} {$this->lastName} ({$this->email})";
    }

    // Your other user methods...
}
```

### Example 6: Laravel Integration

```php
<?php

// app/UserSwitcher/LaravelUserProvider.php
namespace App\UserSwitcher;

use App\Models\User;
use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserProviderInterface;
use Cdoebler\GenericUserSwitcher\Generic\GenericUser;

class LaravelUserProvider implements UserProviderInterface
{
    public function getUsers(): array
    {
        return User::orderBy('name')
            ->get()
            ->map(fn($user) => new GenericUser($user->id, $user->name))
            ->all();
    }

    public function findUserById(string|int $identifier): ?UserInterface
    {
        $user = User::find($identifier);

        return $user
            ? new GenericUser($user->id, $user->name)
            : null;
    }
}

// app/Http/Middleware/HandleUserSwitcher.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleUserSwitcher
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('_switch_user') && app()->environment('local')) {
            $impersonator = app(\Cdoebler\GenericUserSwitcher\Generic\SessionImpersonator::class);

            if ($request->get('_switch_user') === '_stop') {
                $impersonator->stopImpersonating();
            } else {
                $impersonator->impersonate($request->get('_switch_user'));
            }

            return redirect($request->url());
        }

        return $next($request);
    }
}

// resources/views/layouts/app.blade.php
@if(app()->environment('local'))
    {!! app(\Cdoebler\GenericUserSwitcher\Renderer\UserSwitcherRenderer::class)->render() !!}
@endif
```

### Example 7: Symfony Integration

```php
<?php

// src/UserSwitcher/DoctrineUserProvider.php
namespace App\UserSwitcher;

use App\Entity\User;
use Cdoebler\GenericUserSwitcher\Generic\GenericUser;
use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserProvider implements UserProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function getUsers(): array
    {
        $users = $this->entityManager
            ->getRepository(User::class)
            ->findBy([], ['email' => 'ASC']);

        return array_map(
            fn(User $user) => new GenericUser($user->getId(), $user->getEmail()),
            $users
        );
    }

    public function findUserById(string|int $identifier): ?UserInterface
    {
        $user = $this->entityManager->find(User::class, $identifier);

        return $user
            ? new GenericUser($user->getId(), $user->getEmail())
            : null;
    }
}

// config/services.yaml
services:
    App\UserSwitcher\DoctrineUserProvider: ~

    Cdoebler\GenericUserSwitcher\Generic\SessionImpersonator: ~

    Cdoebler\GenericUserSwitcher\Renderer\UserSwitcherRenderer:
        arguments:
            $userProvider: '@App\UserSwitcher\DoctrineUserProvider'
            $impersonator: '@Cdoebler\GenericUserSwitcher\Generic\SessionImpersonator'

// templates/base.html.twig
{% if app.environment == 'dev' %}
    {{ render_user_switcher()|raw }}
{% endif %}
```

## Configuration Options

### Renderer Options

The `UserSwitcherRenderer::render()` method accepts an array of options:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `position` | `string` | `'bottom-right'` | Widget position: `top-left`, `top-right`, `bottom-left`, `bottom-right` |
| `z_index` | `int` | `9999` | CSS z-index value for the floating widget |
| `param_name` | `string` | `'_switch_user'` | URL query parameter name used for switching users |

### SessionImpersonator Options

```php
<?php

// Default session key
$impersonator = new SessionImpersonator();

// Custom session key
$impersonator = new SessionImpersonator('my_app_impersonator');
```

## Interface Reference

### UserInterface

```php
interface UserInterface
{
    public function getIdentifier(): string|int;
    public function getDisplayName(): string;
}
```

### UserProviderInterface

```php
interface UserProviderInterface
{
    /** @return array<UserInterface> */
    public function getUsers(): array;

    public function findUserById(string|int $identifier): ?UserInterface;
}
```

### ImpersonatorInterface

```php
interface ImpersonatorInterface
{
    public function impersonate(string|int $identifier): void;
    public function stopImpersonating(): void;
    public function isImpersonating(): bool;
    public function getOriginalUserId(): string|int|null;
}
```

## Security Considerations

This package is designed for **development and debugging purposes only**.

**Important security notes:**

1. **Never enable in production** - User switching should only be available in development/staging environments
2. **Add authorization checks** - Ensure only authorized users (admins, developers) can switch users
3. **Environment gating** - Use environment checks to disable in production:

```php
<?php

// Only show switcher in development
if (getenv('APP_ENV') === 'development') {
    echo $renderer->render();
}
```

4. **Audit logging** - Consider logging all impersonation events for security auditing

### Example: Implementing Audit Logging

The package supports optional audit logging to track all impersonation events:

```php
<?php

use Cdoebler\GenericUserSwitcher\Interfaces\AuditLoggerInterface;

class DatabaseAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private \PDO $pdo,
        private int $currentUserId
    ) {}

    public function logImpersonationStarted(string|int $targetUserId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action, target_user_id, created_at)
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$this->currentUserId, 'impersonation_started', $targetUserId]);
    }

    public function logImpersonationStopped(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action, created_at)
             VALUES (?, ?, NOW())'
        );
        $stmt->execute([$this->currentUserId, 'impersonation_stopped']);
    }
}

// Usage
$auditLogger = new DatabaseAuditLogger($pdo, $currentUser->getId());
$impersonator = new SessionImpersonator('generic_user_switcher_impersonator', $auditLogger);
```

## Development

### Running Tests

```bash
# Run all tests
composer test

# Run only Pest tests
composer pest

# Run PHPStan analysis
composer phpstan

# Run Rector (dry-run)
composer rector-dry

# Apply Rector fixes
composer rector
```

### Code Quality

This package maintains high code quality standards:

- **PHPStan Level 10**: Maximum static analysis strictness
- **Pest Testing**: Modern PHP testing with comprehensive coverage
- **Rector**: Automated code modernization and consistency
- **Architecture Tests**: Enforces coding standards and best practices

## Architecture

```
┌─────────────────────────────────┐
│   UserSwitcherRenderer          │
│   (Frontend Component)          │
└────────┬────────────┬───────────┘
         │            │
         ▼            ▼
┌──────────────┐  ┌──────────────────┐
│UserProvider  │  │  Impersonator    │
│Interface     │  │  Interface       │
└──────┬───────┘  └────────┬─────────┘
       │                   │
       ▼                   ▼
┌──────────────┐  ┌──────────────────┐
│InMemory      │  │  Session         │
│UserProvider  │  │  Impersonator    │
└──────────────┘  └──────────────────┘
       │
       ▼
┌──────────────┐
│UserInterface │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│GenericUser   │
└──────────────┘
```

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass (`composer test`)
2. Code follows PSR-12 standards
3. PHPStan level 10 passes
4. New features include tests

## License

MIT License. See [LICENSE.md](LICENSE.md) file for details.

## Author

**Christian Doebler**
- Email: mail@christian-doebler.net
- GitHub: [@cdoebler](https://github.com/cdoebler)

## Changelog

### 1.0.0 (Current)
- Initial release
- Framework-agnostic user switching
- Session-based impersonation
- Frontend UI component
- In-memory user provider
- Generic user implementation
- Full test coverage
