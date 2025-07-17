# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Development Commands

### Linting and Code Quality
- **Run all linting checks**: `composer lint` (runs both PHPCS and PHPStan)
- **Run PHP CodeSniffer**: `composer phpcs`
- **Fix code style issues**: `composer phpcbf`
- **Run PHPStan static analysis**: `composer phpstan`

### Testing
- **Run PHPUnit tests**: `composer phpunit`

### Dependencies
- **Install dependencies**: `composer install`
- **Update dependencies**: `composer update`

## High-level Architecture

This project is a PHP AI Client SDK designed to provide a provider-agnostic API for interacting with various generative AI models. The architecture has several key design principles:

### API Layers

1. **Implementer API**: For developers using the SDK to add AI features to their applications
   - Fluent API: Chain methods for readable, declarative code (e.g., `AiClient::prompt('...')->generateText()`)
   - Traditional API: Method-based approach with arrays of arguments
   
2. **Extender API**: For developers adding new providers, models, or extending functionality
   - Provider Registry system for managing available AI providers
   - Model discovery based on capabilities and requirements

### Core Concepts

- **Provider Agnostic**: Abstracts away provider-specific details, allowing code to work with any AI provider (Google, OpenAI, Anthropic, etc.)
- **Capability-Based Model Selection**: Models can be discovered and selected based on their supported capabilities (text generation, image generation, etc.) and options (input modalities, output formats, etc.)
- **Uniform Data Structures**: Consistent message formats, file representations, and results across all providers
- **Modality Support**: Designed to support arbitrary combinations of input/output modalities (text, image, audio, video)

### Namespace Structure

The codebase follows a structured namespace hierarchy under `WordPress\AiClient`:
- `Builders`: Fluent API builders (PromptBuilder, MessageBuilder)
- `Embeddings`: Embedding-related data structures
- `Files`: File handling contracts and implementations
- `Messages`: Message DTOs and enums
- `Operations`: Long-running operation support
- `Providers`: Provider system with contracts, models, and registry
- `Results`: Result data structures and transformations
- `Tools`: Function calling and tool support
- `Util`: Utility classes for common operations

### Key Design Patterns

- **Interface Segregation**: Separate interfaces for different model capabilities (TextGenerationModelInterface, ImageGenerationModelInterface, etc.)
- **Composition over Inheritance**: Models compose capabilities through interfaces rather than inheritance
- **DTO Pattern**: Data Transfer Objects for messages, results, and configurations with JSON schema support
- **Builder Pattern**: Fluent builders for constructing prompts and messages

### PHP Compatibility

- Minimum PHP version: 7.4
- Follows PER Coding Style
- Uses type hints wherever possible within PHP 7.4 constraints
