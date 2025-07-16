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
- Classes, interfaces, and traits, and namespaces are not prefixed with `Ai`, exluding the root namespace.

## PHP Compatibility

All code must be backward compatible with PHP 7.4, which is the minimum required PHP version for this project.

## Guidelines

- As with all WordPress projects, we want to ensure a welcoming environment for everyone. With that in mind, all contributors are expected to follow our [Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/).
- All WordPress projects are [licensed under the GPLv2+](/LICENSE.md), and all contributions to the PHP AI Client package will be released under the GPLv2+ license. You maintain copyright over any contribution you make, and by submitting a pull request, you are agreeing to release that contribution under the GPLv2+ license.
