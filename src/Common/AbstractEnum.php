<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common;

use BadMethodCallException;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Abstract base class for enum-like behavior in PHP 7.4
 *
 * This class provides enum-like functionality for PHP versions that don't support native enums.
 * Child classes should define uppercase snake_case constants for enum values.
 *
 * @example
 * class PersonEnum extends AbstractEnum {
 *     public const FIRST_NAME = 'first';
 *     public const LAST_NAME = 'last';
 * }
 *
 * // Usage:
 * $enum = PersonEnum::from('first'); // Creates instance with value 'first'
 * $enum = PersonEnum::tryFrom('invalid'); // Returns null
 * $enum = PersonEnum::firstName(); // Creates instance with value 'first'
 * $enum->name; // 'FIRST_NAME'
 * $enum->value; // 'first'
 * $enum->equals('first'); // Returns true
 * $enum->is(PersonEnum::firstName()); // Returns true
 * PersonEnum::cases(); // Returns array of all enum instances
 */
abstract class AbstractEnum
{
    /**
     * @var string|int The value of the enum instance
     */
    private $value;

    /**
     * @var string The name of the enum constant
     */
    private $name;

    /**
     * @var array<string, array<string, string|int>> Cache for reflection data
     */
    private static $cache = [];

    /**
     * @var array<string, array<string, self>> Cache for enum instances
     */
    private static $instances = [];

    /**
     * Constructor is private to ensure instances are created through static methods
     *
     * @param string|int $value The enum value
     * @param string $name The constant name
     */
    private function __construct($value, string $name)
    {
        $this->value = $value;
        $this->name = $name;
    }

    /**
     * Magic getter to provide read-only access to properties
     *
     * @param string $property The property name
     * @return mixed
     * @throws BadMethodCallException If property doesn't exist
     */
    public function __get(string $property)
    {
        if ($property === 'value' || $property === 'name') {
            return $this->$property;
        }

        throw new BadMethodCallException(
            sprintf('Property %s::%s does not exist', static::class, $property)
        );
    }

    /**
     * Magic setter to prevent property modification
     *
     * @param string $property The property name
     * @param mixed $value The value to set
     * @throws BadMethodCallException Always, as enum properties are read-only
     */
    public function __set(string $property, $value): void
    {
        throw new BadMethodCallException(
            sprintf('Cannot modify property %s::%s - enum properties are read-only', static::class, $property)
        );
    }

    /**
     * Create an enum instance from a value, throws exception if invalid
     *
     * @param string|int $value The enum value
     * @return static
     * @throws InvalidArgumentException If the value is not valid
     */
    public static function from($value): self
    {
        $instance = self::tryFrom($value);
        if ($instance === null) {
            throw new InvalidArgumentException(
                sprintf('%s is not a valid backing value for enum %s', (string) $value, static::class)
            );
        }
        return $instance;
    }

    /**
     * Try to create an enum instance from a value, returns null if invalid
     *
     * @param string|int $value The enum value
     * @return static|null
     */
    public static function tryFrom($value): ?self
    {
        $constants = self::getConstants();
        foreach ($constants as $name => $constantValue) {
            if ($constantValue === $value) {
                return self::getInstance($constantValue, $name);
            }
        }
        return null;
    }

    /**
     * Get all enum cases
     *
     * @return static[]
     */
    public static function cases(): array
    {
        $cases = [];
        $constants = self::getConstants();
        foreach ($constants as $name => $value) {
            $cases[] = self::getInstance($value, $name);
        }
        return $cases;
    }

    /**
     * Check if this enum has the same value as the given value
     *
     * @param string|int|self $other The value or enum to compare
     * @return bool
     */
    public function equals($other): bool
    {
        if ($other instanceof self) {
            return $this->is($other);
        }

        return $this->value === $other;
    }

    /**
     * Check if this enum is the same instance type and value as another enum
     *
     * @param self $other The other enum to compare
     * @return bool
     */
    public function is(self $other): bool
    {
        return $this === $other; // Since we're using singletons, we can use identity comparison
    }

    /**
     * Get all valid values for this enum
     *
     * @return array<string, string|int>
     */
    public static function getValues(): array
    {
        return self::getConstants();
    }

    /**
     * Check if a value is valid for this enum
     *
     * @param string|int $value The value to check
     * @return bool
     */
    public static function isValidValue($value): bool
    {
        return in_array($value, self::getValues(), true);
    }

    /**
     * Create an enum instance from a value (deprecated, use from() instead)
     *
     * @param string|int $value The enum value
     * @return static
     * @throws InvalidArgumentException If the value is not valid
     * @deprecated Use from() method instead
     */
    public static function fromValue($value): self
    {
        return self::from($value);
    }

    /**
     * Get or create a singleton instance for the given value and name
     *
     * @param string|int $value The enum value
     * @param string $name The constant name
     * @return static
     * @phpstan-return static
     */
    private static function getInstance($value, string $name): self
    {
        $className = static::class;

        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = [];
        }

        if (!isset(self::$instances[$className][$name])) {
            $instance = new $className($value, $name);
            self::$instances[$className][$name] = $instance;
        }

        /** @var static */
        return self::$instances[$className][$name];
    }

    /**
     * Get all constants for this enum class
     *
     * @return array<string, string|int>
     * @throws \RuntimeException If invalid constant found
     */
    protected static function getConstants(): array
    {
        $className = static::class;

        if (!isset(self::$cache[$className])) {
            $reflection = new ReflectionClass($className);
            $constants = $reflection->getConstants();

            // Validate all constants
            $enumConstants = [];
            foreach ($constants as $name => $value) {
                // Check if constant name follows uppercase snake_case pattern
                if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Invalid enum constant name "%s" in %s. Constants must be UPPER_SNAKE_CASE.',
                            $name,
                            $className
                        )
                    );
                }

                // Check if value is valid type
                if (!is_string($value) && !is_int($value)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Invalid enum value type for constant %s::%s. ' .
                            'Only string and int values are allowed, %s given.',
                            $className,
                            $name,
                            gettype($value)
                        )
                    );
                }

                $enumConstants[$name] = $value;
            }

            self::$cache[$className] = $enumConstants;
        }

        return self::$cache[$className];
    }

    /**
     * Handle dynamic method calls for enum checking
     *
     * @param string $name The method name
     * @param array<mixed> $arguments The method arguments
     * @return bool
     * @throws BadMethodCallException If the method doesn't exist
     */
    public function __call(string $name, array $arguments)
    {
        // Handle is* methods
        if (strpos($name, 'is') === 0) {
            $constantName = self::camelCaseToConstant(substr($name, 2));
            $constants = self::getConstants();

            if (isset($constants[$constantName])) {
                return $this->value === $constants[$constantName];
            }
        }

        throw new BadMethodCallException(
            sprintf('Method %s::%s does not exist', static::class, $name)
        );
    }

    /**
     * Handle static method calls for enum creation
     *
     * @param string $name The method name
     * @param array<mixed> $arguments The method arguments
     * @return static
     * @throws BadMethodCallException If the method doesn't exist
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $constantName = self::camelCaseToConstant($name);
        $constants = self::getConstants();

        if (isset($constants[$constantName])) {
            return self::getInstance($constants[$constantName], $constantName);
        }

        throw new BadMethodCallException(
            sprintf('Method %s::%s does not exist', static::class, $name)
        );
    }

    /**
     * Convert camelCase to CONSTANT_CASE
     *
     * @param string $camelCase The camelCase string
     * @return string The CONSTANT_CASE version
     */
    private static function camelCaseToConstant(string $camelCase): string
    {
        $snakeCase = preg_replace('/([a-z])([A-Z])/', '$1_$2', $camelCase);
        if ($snakeCase === null) {
            return strtoupper($camelCase);
        }
        return strtoupper($snakeCase);
    }

    /**
     * String representation of the enum
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
