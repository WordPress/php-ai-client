---
agent: 'Plan'
description: 'Generate TypeScript definitions from PHP DTOs and Enums'
---

You are an expert TypeScript developer. Your task is to generate TypeScript definitions from the PHP codebase in this repository.

## Goal
Generate two files in the repository root:
1. `enums.ts`: Contains all Enum definitions.
2. `types.ts`: Contains all DTO (Data Transfer Object) definitions.

## Source Files
- **DTOs:** Scan all PHP files in `src/**/DTO/*.php`.
- **Enums:** Scan all PHP files in `src/**/Enums/*.php`.

## Exclusions
**DO NOT** generate types for the following, even if they are in a DTO or Enum directory:
- HTTP transport classes (e.g., `Request`, `Response`, `HttpMethodEnum`). **EXCEPTION:** `RequestOptions` MUST be included.
- Model requirement classes (e.g., `ModelRequirements`, `RequiredOption`).
- `ProviderModelsMetadata`.

## Rules for `enums.ts`
1. **Header:** The file MUST start with the following comment:
   ```typescript
   /**
    * TypeScript definitions for PHP AI Client SDK Enums.
    *
    * This file is auto-generated based on the PHP Enum classes. DO NOT MODIFY IT MANUALLY.
    */
   ```
2. **Format:** Export a `const` object with `as const` and a corresponding `type` definition.
3. **Naming:**
   - The `const` object name must match the PHP Enum class name (remove `Enum` suffix if it exists, e.g., `FileTypeEnum` -> `FileType`).
   - Keys in the `const` object must be **UPPERCASE**.
   - Values must be the actual string/int values from the PHP Enum.
4. **Type Definition:** The type must be derived from the `const` object using `typeof EnumName[keyof typeof EnumName]`.

**Example:**
```typescript
export const FileType = {
    INLINE: 'inline',
    REMOTE: 'remote',
} as const;
export type FileType = typeof FileType[keyof typeof FileType];
```

## Rules for `types.ts`
1. **Header:** The file MUST start with the following comment:
   ```typescript
   /**
    * TypeScript definitions for PHP AI Client SDK DTOs.
    *
    * This file is auto-generated based on the PHP DTO classes. DO NOT MODIFY IT MANUALLY.
    */
   ```
2. **Imports:** Import all necessary Enum types from `./enums`.
3. **Format:** Use `type` aliases. **Do not use `interface`.**
4. **Naming:** Match the PHP DTO class name.
5. **Properties:**
   - Use camelCase for property names (matching the keys in the PHP `toArray()` method).
   - **Nullable Types:** If a PHP property is nullable (`?string` or `string|null`), make the TypeScript property **optional** (`prop?: string`). **Do not use `string | null`.**
6. **Strict Typing:**
   - **NEVER use `any`.**
   - Use `unknown` for mixed/unspecified values.
   - Use `Record<string, unknown>` for associative arrays or "additional data".
7. **Arrays:** Use `Type[]` syntax.

**Example:**
```typescript
import type { FileType } from './enums';

export type File = {
    fileType: FileType;
    mimeType: string;
    url?: string;       // PHP: ?string
    base64Data?: string;
    metadata?: Record<string, unknown>; // PHP: array
};
```

## Execution
Read the PHP files, analyze their structure, and generate the TypeScript files according to these strict rules.
