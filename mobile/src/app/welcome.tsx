import { useRouter } from 'expo-router';
import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button, Hero } from '../components';
import { APP_NAME } from '../config/app';
import { useTheme } from '../theme';

/**
 * The first screen anyone sees.
 *
 * Says what the app is in one line and offers exactly two ways forward. It
 * deliberately does not sell: a person who tapped a partner's invite link is
 * here to join someone, not to be marketed at.
 */
export default function WelcomeScreen() {
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();
  const router = useRouter();
  const body = scaleType('body');

  return (
    <ScrollView
      testID="screen-welcome"
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.xl,
        paddingBottom: insets.bottom + spacing.xxl,
        gap: spacing.xl,
        flexGrow: 1,
      }}
    >
      <Hero overline="Welcome to" title={APP_NAME} accent="for two" />

      <Text
        style={{
          color: colours.ink,
          fontSize: body.size,
          lineHeight: body.lineHeight,
          fontFamily: body.family,
        }}
      >
        A private space for the two of you. No feed, no followers, no one else 🔒
      </Text>

      <View style={{ flexGrow: 1 }} />

      {/*
        router.push rather than `<Link asChild>` wrapping a Button whose own
        onPress was a no-op. That worked — Link's clone overrode the prop —
        but it read as a dead button, and dead-looking is indistinguishable
        from dead at review time, which is how the rest of this screen's
        problems got missed.
      */}
      <View style={{ gap: spacing.md }}>
        <Button
          label="Get started"
          size="large"
          emoji="💞"
          onPress={() => router.push('/sign-up')}
          testID="go-sign-up"
        />
        <Button
          label="I already have an account"
          variant="ghost"
          onPress={() => router.push('/sign-in')}
          testID="go-sign-in"
        />
      </View>
    </ScrollView>
  );
}
