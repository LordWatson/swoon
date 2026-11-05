import type { ExpoConfig } from 'expo/config';

/**
 * Expo config as TypeScript, so the API URL and product name come from the
 * environment rather than being frozen into app.json.
 *
 * The product name lives here and in `api/config/swoon.php` — nowhere else.
 */
const APP_NAME = process.env.EXPO_PUBLIC_APP_NAME ?? 'Swoon';

/**
 * Phones and emulators cannot resolve Herd's `.test` domain, so the default is
 * only right for the iOS simulator. See docs/DEV_TOOLING.md.
 */
const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'https://swoon-api.test';

const config: ExpoConfig = {
  name: APP_NAME,
  slug: 'swoon',
  version: '0.1.0',
  orientation: 'portrait',
  icon: './assets/images/icon.png',
  // Must match api/config/swoon.php → deep_link.scheme
  scheme: 'swoon',
  userInterfaceStyle: 'automatic',

  ios: {
    supportsTablet: false,
    bundleIdentifier: 'test.swoon.app',
    // Partner invite universal links; needs the AASA file hosted on the host.
    associatedDomains: ['applinks:link.swoon.test'],
  },

  android: {
    package: 'test.swoon.app',
    adaptiveIcon: {
      foregroundImage: './assets/images/android-icon-foreground.png',
      backgroundImage: './assets/images/android-icon-background.png',
      monochromeImage: './assets/images/android-icon-monochrome.png',
      // Cream, matching colours.background. This is a NATIVE manifest value
      // baked in at build time, so it cannot read the TypeScript theme.
      // Keep it in step with src/theme/colours.ts by hand.
      // eslint-disable-next-line no-restricted-syntax
      backgroundColor: '#FFF8F2',
    },
    intentFilters: [
      {
        action: 'VIEW',
        autoVerify: true,
        data: [{ scheme: 'https', host: 'link.swoon.test', pathPrefix: '/invite' }],
        category: ['BROWSABLE', 'DEFAULT'],
      },
    ],
  },

  web: { output: 'server', favicon: './assets/images/favicon.png' },

  plugins: [
    'expo-router',
    'expo-font',
    [
      'expo-splash-screen',
      {
        // Native splash value - same constraint as the adaptive icon above.
        // eslint-disable-next-line no-restricted-syntax
        backgroundColor: '#FFF8F2',
        image: './assets/images/splash-icon.png',
        imageWidth: 120,
      },
    ],
  ],

  experiments: { typedRoutes: true, reactCompiler: true },

  // Read by src/config/app.ts
  extra: {
    appName: APP_NAME,
    apiUrl: API_URL,
  },
};

export default config;
