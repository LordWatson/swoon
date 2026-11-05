import Constants from 'expo-constants';

/**
 * The product name. This and `api/config/swoon.php → 'name'` are the only two
 * places it lives. Renaming the product means changing these two values.
 */
export const APP_NAME = (Constants.expoConfig?.extra?.appName as string | undefined) ?? 'Swoon';

/**
 * API base URL, from Expo config (which reads the environment).
 *
 * Herd serves the API at https://swoon-api.test, but a phone or emulator cannot
 * resolve `.test`. Per target:
 *
 *   iOS simulator     https://swoon-api.test        (shares the Mac's resolver)
 *   Android emulator  http://10.0.2.2:8000          (the host's loopback)
 *   Physical device   the `herd share` URL, or http://<LAN-IP>:8000
 *
 * Set EXPO_PUBLIC_API_URL to override. See docs/DEV_TOOLING.md.
 */
export const API_BASE_URL =
  (Constants.expoConfig?.extra?.apiUrl as string | undefined) ??
  process.env.EXPO_PUBLIC_API_URL ??
  'https://swoon-api.test';

/** Deep-link scheme; must match `api/config/swoon.php → deep_link.scheme`. */
export const DEEP_LINK_SCHEME = (Constants.expoConfig?.scheme as string | undefined) ?? 'swoon';
