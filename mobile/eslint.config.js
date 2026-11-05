// https://docs.expo.dev/guides/using-eslint/
const { defineConfig } = require('eslint/config');
const expoConfig = require('eslint-config-expo/flat');
const prettierConfig = require('eslint-config-prettier/flat');

/**
 * Raw design values are banned outside src/theme.
 *
 * Implemented with the built-in `no-restricted-syntax` rather than a bespoke
 * plugin: it needs no extra dependency and no build step, and the message is
 * what a developer actually needs to read. See docs/BRAND.md § 9.
 */
const noRawDesignValues = [
  'error',
  {
    selector: 'Literal[value=/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/]',
    message:
      'Raw hex colour. Use a theme token: const { colours } = useTheme(). ' +
      'Colours live only in src/theme/colours.ts — see docs/BRAND.md.',
  },
  {
    selector: 'Literal[value=/^(?:rgba?|hsla?)\\(/]',
    message:
      'Raw colour function. Use a theme token from useTheme(). ' +
      'Colours live only in src/theme/colours.ts — see docs/BRAND.md.',
  },
  {
    selector: 'Literal[value=/^(?:Fraunces|Nunito)(?:_|$)/]',
    message:
      'Raw font family. Use theme.typography / theme.fontFamilies. ' +
      'Font names live only in src/theme/typography.ts — see docs/BRAND.md.',
  },
  {
    selector: "Property[key.name='fontFamily'] > Literal",
    message: 'Inline fontFamily. Use a typography token: theme.typography.body.family.',
  },
];

module.exports = defineConfig([
  expoConfig,
  prettierConfig,
  {
    ignores: ['dist/*', 'node_modules/*', '.expo/*', 'expo-env.d.ts'],
  },
  {
    rules: {
      'no-restricted-syntax': noRawDesignValues,
      eqeqeq: ['error', 'always', { null: 'ignore' }],
      'no-console': ['warn', { allow: ['warn', 'error'] }],
    },
  },
  {
    // The theme is where these values are DEFINED, so the ban cannot apply here.
    files: ['src/theme/**'],
    rules: { 'no-restricted-syntax': 'off' },
  },
  {
    // Tests assert on concrete colour values on purpose.
    files: ['**/__tests__/**', '**/*.test.ts', '**/*.test.tsx'],
    rules: { 'no-restricted-syntax': 'off' },
  },
]);
