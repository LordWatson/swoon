import {
  Fraunces_600SemiBold,
  Fraunces_700Bold,
  useFonts as useFraunces,
} from '@expo-google-fonts/fraunces';
import {
  Nunito_400Regular,
  Nunito_600SemiBold,
  Nunito_700Bold,
  useFonts as useNunito,
} from '@expo-google-fonts/nunito';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useMemo } from 'react';
import { View } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { ApiError } from '../api/client';
import { AuthGate } from '../features/accounts/AuthGate';
import { installTokenReader } from '../features/accounts/useAuth';
import { ThemeProvider, useSystemTheme } from '../theme';

/**
 * One QueryClient for the app.
 *
 * Retry is deliberately conservative and offline-aware: an `ApiError` with
 * status 0 (no network) should not burn retries before the UI can show its
 * offline state (UX rule 11), and a 401 cannot be fixed by trying again — it
 * means the token is dead and the app should get on with showing Welcome.
 */
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      refetchOnWindowFocus: false,
      retry: (count, error) => {
        if (error instanceof ApiError && !error.isRetryable) return false;

        return count < 1;
      },
    },
  },
});

// Point the API client at secure storage before anything renders. Done at
// module scope rather than in an effect so the very first request already has
// a token to send.
installTokenReader();

export default function RootLayout() {
  const [frauncesLoaded] = useFraunces({ Fraunces_600SemiBold, Fraunces_700Bold });
  const [nunitoLoaded] = useNunito({
    Nunito_400Regular,
    Nunito_600SemiBold,
    Nunito_700Bold,
  });

  const theme = useSystemTheme();
  const fontsReady = frauncesLoaded && nunitoLoaded;

  // Hold the splash on the themed background rather than flashing white, so
  // the first frame is already Swoon.
  const shell = useMemo(
    () => ({ flex: 1, backgroundColor: theme.colours.background }),
    [theme.colours.background],
  );

  return (
    <SafeAreaProvider>
      <ThemeProvider value={theme}>
        <QueryClientProvider client={queryClient}>
          <StatusBar style={theme.scheme === 'dark' ? 'light' : 'dark'} />
          <View style={shell}>
            {fontsReady ? (
              <AuthGate>
                <Stack
                  screenOptions={{
                    headerShown: false,
                    contentStyle: { backgroundColor: theme.colours.background },
                  }}
                />
              </AuthGate>
            ) : null}
          </View>
        </QueryClientProvider>
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
