# Prepublish Checklist

Before publishing a new release, you MUST check all of the following:

- Run integration tests to verify provider functionality: `composer test:integration`
- Ensure there is no `n.e.x.t` usage in any PHP file.
  - If there is, replace these `n.e.x.t` strings with the new release's version number.
- Ensure the [`WordPress\AiClient\AiClient`](https://github.com/WordPress/php-ai-client/blob/trunk/src/AiClient.php) `VERSION` constant is set to the new release's version number.

**Note:** In the future we may provide CI and tooling for this, but for now this needs to be manually verified.
