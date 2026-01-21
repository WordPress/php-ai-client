# Contributing to the PHP AI Client package

Thank you for your interest in contributing to the PHP AI Client package! Here you find some information on how to get started. As this repository is in its very early stages, expect more detailed instructions to come soon.

## Coding standards

While this project is stewarded by [WordPress AI Team](https://make.wordpress.org/ai/) members and contributors, it is a WordPress agnostic PHP package that can benefit any project in the PHP ecosystem. As such, all code must follow the [PER Coding Style](https://www.php-fig.org/per/coding-style/), which extends [PSR-12](https://www.php-fig.org/psr/psr-12/). Note that the PHPCS config is using PSR-12 due to the fact that it does not support PER, yet. So PER is preferred, but PSR-12 is acceptable.

All parameters, return values, and properties must use explicit type hints, except in cases where providing the correct type hint would be impossible given limitations of the oldest supported PHP version (see below).

## Naming conventions

The following naming conventions must be followed for consistency and autoloading:

- Interfaces are suffixed with `Interface`.
- Traits are suffixed with `Trait`.
- Enums are suffixed with `Enum`.
- File names are the same as the class, trait, and interface name for PSR-4 autoloading.
- Classes, interfaces, and traits, and namespaces are not prefixed with `Ai`, excluding the root namespace.

## Documentation standards

All code must be properly documented with PHPDoc blocks following these standards:

### General rules

- All descriptions must end with a period.
- Use `@since n.e.x.t` for new code (will be replaced with actual version on release).
- Place `@since` tags below the description and above `@param` tags, with blank comment lines around it.

### Method documentation

- Method descriptions must start with a third-person verb (e.g., "Creates", "Returns", "Checks").
- Exceptions: Constructors and magic methods may use different phrasing.
- All `@return` annotations must include a description.

### Interface implementations

- Use `{@inheritDoc}` instead of duplicating descriptions when implementing interface methods.
- Only provide a unique description if it adds value beyond the interface documentation.

### Example

```php
/**
 * Class for handling user authentication requests.
 *
 * @since n.e.x.t
 */
class AuthHandler
{
    /**
     * Validates user credentials against the database.
     *
     * @since n.e.x.t
     * 
     * @param string $username The username to validate.
     * @param string $password The password to validate.
     * @return bool True if credentials are valid, false otherwise.
     */
    public function validate(string $username, string $password): bool
    {
        // Implementation
    }
}
```

### Array Lists

When an array is a list — that is, an array where the keys are sequential, starting at 0 — use the `list` generic type within the docblock. For example, a parameter that is a list of strings would be documented as `@param list<string> $variable`.

Note that `list<string>` and `string[]` _are not_ the same. The latter is an alias for `array<int, string>` which does not enforce that the keys are sequential. That particular syntax, therefore, will rarely be used.

## PHP Compatibility

All code must be backward compatible with PHP 7.4, which is the minimum required PHP version for this project.

## Running Tests

### Unit Tests

```bash
composer test:unit
```

### Integration Tests

Integration tests make real API calls and require provider API keys. Create a `.env` file in the project root:

```
OPENAI_API_KEY=sk-...
GOOGLE_API_KEY=...
ANTHROPIC_API_KEY=sk-ant-...
```

Then run:

```bash
composer test:integration
```

Tests for providers without keys will be skipped automatically.

## Branch naming conventions

There are a few protected branch naming conventions:

* `trunk`: The main development branch.
* `release/*`: A branch for a specific release, useful e.g. for applying a hotfix.
* `feature/*`: A branch for a larger feature that takes multiple iterative PRs towards completion.

These special branches are protected and are configured more strictly in regards to GitHub workflow configuration.

Branches that you use for implementing a pull request or experimenting can use any naming convention you prefer, _except_ the above. Additionally, please do not use branch names that would easily cause confusion, such as other common main branch names like `main` or `develop`.

Ideally, the branch name is in some form or shape descriptive of what it is for.

## Guidelines

- As with all WordPress projects, we want to ensure a welcoming environment for everyone. With that in mind, all contributors are expected to follow our [Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/).
- All WordPress projects are [licensed under the GPLv2+](/LICENSE.md), and all contributions to the PHP AI Client package will be released under the GPLv2+ license. You maintain copyright over any contribution you make, and by submitting a pull request, you are agreeing to release that contribution under the GPLv2+ license.
