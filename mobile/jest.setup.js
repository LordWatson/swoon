/* global jest */

// Keep tests deterministic and offline: nothing here may reach the network.
global.fetch = jest.fn(() =>
  Promise.reject(new Error('fetch is not stubbed in this test — mock it explicitly')),
);

// expo-constants reads app.config.ts at runtime; in tests we pin the values the
// config module expects so API_BASE_URL and APP_NAME are predictable.
jest.mock('expo-constants', () => ({
  __esModule: true,
  default: {
    expoConfig: {
      scheme: 'swoon',
      extra: { appName: 'Swoon', apiUrl: 'http://localhost' },
    },
  },
}));
