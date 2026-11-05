import { useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect } from 'react';
import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError } from '../../api/client';
import { Button, Card, Hero } from '../../components';
import { previewInvite } from '../../features/accounts/api';
import { InviteCodeShape } from '../../features/accounts/inviteCode';
import { clearPendingInvite, rememberPendingInvite } from '../../features/accounts/pendingInvite';
import { useAcceptInvite, useAuthState } from '../../features/accounts/useAuth';
import { useTheme } from '../../theme';
import { useQuery } from '@tanstack/react-query';

/**
 * Accept invite — where every invite link lands.
 *
 * Reached from `swoon://invite/<code>` and
 * `https://link.swoon.test/invite/<code>`, from a cold start as well as a warm
 * one. Three jobs:
 *
 * 1. **Show who is inviting you before asking you to do anything.** The
 *    preview is unauthenticated and returns a first name only, so this works
 *    for someone who has never opened the app.
 * 2. **Stash the code immediately.** A cold start after an app install carries
 *    no URL, so a person who taps a link, visits the store and comes back
 *    would otherwise lose it entirely. This is the install detour, and
 *    stashing on sight is the only thing that survives it.
 * 3. **Resolve it when there is an account to attach it to** — not before.
 *
 * A dead code gets a warm, specific message rather than a 404 screen: the
 * person reading it did nothing wrong, their partner's code simply ran out.
 */
export default function AcceptInviteScreen() {
  const { code: raw } = useLocalSearchParams<{ code: string }>();
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();
  const router = useRouter();
  const authState = useAuthState();
  const accept = useAcceptInvite();

  const code = InviteCodeShape.normalise(raw ?? '');
  const wellFormed = InviteCodeShape.looksValid(code);

  const preview = useQuery({
    queryKey: ['invite-preview', code],
    queryFn: ({ signal }) => previewInvite(code, signal),
    enabled: wellFormed,
    retry: false,
  });

  // Stash on sight, before anything else can go wrong.
  useEffect(() => {
    if (wellFormed) void rememberPendingInvite(code);
  }, [code, wellFormed]);

  // Someone who is already linked has nothing to do here.
  useEffect(() => {
    if (authState === 'coupled') {
      void clearPendingInvite();
      router.replace('/(tabs)/index');
    }
  }, [authState, router]);

  const body = scaleType('body');
  const caption = scaleType('caption');

  const bodyStyle = {
    color: colours.ink,
    fontSize: body.size,
    lineHeight: body.lineHeight,
    fontFamily: body.family,
  };

  const captionStyle = {
    color: colours.inkMuted,
    fontSize: caption.size,
    lineHeight: caption.lineHeight,
    fontFamily: caption.family,
  };

  const firstName = preview.data?.inviter_first_name ?? 'Someone';
  const unusable = preview.data?.is_usable === false;
  const notFound =
    !wellFormed || (preview.error instanceof ApiError && preview.error.status === 404);

  return (
    <ScrollView
      testID="screen-accept-invite"
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxl,
        gap: spacing.lg,
      }}
    >
      {notFound ? (
        <>
          <Hero overline="Hmm" title="That code" accent="did not work" />
          <Card title="Worth a second look" emoji="🔍">
            <Text style={bodyStyle}>
              Codes are eight characters, and they skip the letters that look like numbers 🔤
            </Text>
            <Text style={captionStyle}>
              Ask your partner to send it again, or to make a fresh one.
            </Text>
          </Card>
        </>
      ) : unusable ? (
        <>
          <Hero overline={`${firstName} invited you`} title="That code" accent="has expired" />
          <Card title="Easily fixed" emoji="⏳">
            <Text style={bodyStyle}>
              Codes last 14 days. Ask {firstName} for a fresh one and we will be right here 💞
            </Text>
          </Card>
        </>
      ) : (
        <>
          <Hero
            overline={preview.isPending ? 'One moment' : `${firstName} would like`}
            title="to share a space"
            accent="with you"
          />

          <Card overline="Just the two of you" title="No feed, no followers" emoji="🔒">
            <Text style={bodyStyle}>Everything in here stays between you and {firstName} 💞</Text>
          </Card>

          {accept.error instanceof ApiError ? (
            <Text style={{ ...captionStyle, color: colours.dangerText }} testID="accept-error">
              {accept.error.errors?.code?.[0] ?? accept.error.message}
            </Text>
          ) : null}

          <View style={{ gap: spacing.md }}>
            {authState === 'unauthenticated' ? (
              <>
                {/*
                  Not signed in: the code is already stashed, so sign-up can
                  send them straight back here afterwards. The invite screen
                  picks the stashed code up.
                */}
                <Button
                  label={`Join ${firstName}`}
                  size="large"
                  emoji="💞"
                  onPress={() => router.push('/sign-up')}
                  testID="join-sign-up"
                />
                <Button
                  label="I already have an account"
                  variant="ghost"
                  onPress={() => router.push('/sign-in')}
                  testID="join-sign-in"
                />
              </>
            ) : (
              <Button
                label={`Join ${firstName}`}
                size="large"
                emoji="💞"
                loading={accept.isPending}
                onPress={() =>
                  accept.mutate(code, {
                    onSuccess: () => {
                      void clearPendingInvite();
                      router.replace('/(tabs)/index');
                    },
                  })
                }
                testID="accept-invite"
              />
            )}
          </View>
        </>
      )}
    </ScrollView>
  );
}
