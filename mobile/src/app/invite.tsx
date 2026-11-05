import { useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { ScrollView, Share, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError } from '../api/client';
import { AvatarPair, Button, Card, Hero } from '../components';
import { readPendingInvite } from '../features/accounts/pendingInvite';
import { useCreateInvite, useMe, useRemindInvite, useSignOut } from '../features/accounts/useAuth';
import { useTheme } from '../theme';

/**
 * Invite your partner — the only screen a signed-in but solo user sees.
 *
 * Shows the code **as well as** the share sheet, because a link is useless
 * when the two of you are in the same room and one phone is flat. The code is
 * selectable and set in a wide-tracked monospace-ish size so it can be read
 * out: the alphabet already excludes 0/O and 1/I/L for that reason (rule 7).
 */
export default function InviteScreen() {
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();
  const me = useMe();
  const createInvite = useCreateInvite();
  const remind = useRemindInvite();
  const signOut = useSignOut();
  const [shared, setShared] = useState(false);
  const router = useRouter();

  // The other half of the install detour. Someone who tapped a link before
  // they had an account arrives here once signed in — with the code stashed
  // but the URL long gone — so this is where it gets resolved.
  useEffect(() => {
    let active = true;

    void readPendingInvite().then((code) => {
      if (active && code !== null) {
        router.replace({ pathname: '/invite/[code]', params: { code } });
      }
    });

    return () => {
      active = false;
    };
  }, [router]);

  const body = scaleType('body');
  const display = scaleType('display');
  const caption = scaleType('caption');

  const invite = createInvite.data;

  return (
    <ScrollView
      testID="screen-invite"
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxl,
        gap: spacing.lg,
      }}
    >
      <Hero
        overline={me.data ? `Hello ${me.data.user.name.split(' ')[0]},` : 'Hello,'}
        title="One more"
        accent="to go"
      >
        <AvatarPair
          tone="feature"
          partners={[
            { name: me.data?.user.name ?? 'You', colour: 'primary', done: true },
            { name: 'Partner', colour: 'ai', done: false },
          ]}
          showPending
        />
      </Hero>

      {invite === undefined ? (
        <Card overline="Nearly there" title="Invite your partner" emoji="💞">
          <View style={{ gap: spacing.md }}>
            <Text
              style={{
                color: colours.ink,
                fontSize: body.size,
                lineHeight: body.lineHeight,
                fontFamily: body.family,
              }}
            >
              We will make you a code to share. It works for 14 days ⏳
            </Text>
            <Button
              label="Make our code"
              size="large"
              emoji="💞"
              loading={createInvite.isPending}
              onPress={() => createInvite.mutate(undefined)}
              testID="make-invite"
            />
          </View>
        </Card>
      ) : (
        <Card overline="Read it out or send it" title="Your code" emoji="🔑">
          <View style={{ gap: spacing.md }}>
            <Text
              selectable
              testID="invite-code"
              style={{
                color: colours.ink,
                fontSize: display.size,
                lineHeight: display.lineHeight,
                fontFamily: display.family,
                letterSpacing: 4,
                textAlign: 'center',
              }}
            >
              {invite.code}
            </Text>

            <Button
              label={shared ? 'Share again' : 'Share the link'}
              size="large"
              emoji="💌"
              onPress={() => {
                setShared(true);
                void Share.share({
                  message: `Join me on Swoon 💞 ${invite.universal_link}`,
                });
              }}
              testID="share-invite"
            />

            <Text
              style={{
                color: colours.inkMuted,
                fontSize: caption.size,
                lineHeight: caption.lineHeight,
                fontFamily: caption.family,
              }}
              testID="waiting-note"
            >
              We will let you know the moment they join 👀
            </Text>

            {/*
              The nudge. Its cooldown is the SERVER'S — a client-side timer is
              advisory, and the point of a cooldown is that it cannot become
              pestering (UX rule 4). So the button is always offered and the
              server's refusal is what the person reads.
            */}
            {invite.email === null ? null : (
              <>
                <Button
                  label="Nudge them"
                  variant="ghost"
                  emoji="👋"
                  loading={remind.isPending}
                  onPress={() => remind.mutate(invite.code)}
                  testID="nudge-partner"
                />
                {remind.error instanceof ApiError ? (
                  <Text
                    style={{
                      color: colours.inkMuted,
                      fontSize: caption.size,
                      lineHeight: caption.lineHeight,
                      fontFamily: caption.family,
                      textAlign: 'center',
                    }}
                    testID="nudge-message"
                  >
                    {remind.error.errors?.code?.[0] ?? remind.error.message}
                  </Text>
                ) : null}
                {remind.isSuccess ? (
                  <Text
                    style={{
                      color: colours.successText,
                      fontSize: caption.size,
                      lineHeight: caption.lineHeight,
                      fontFamily: caption.family,
                      textAlign: 'center',
                    }}
                    testID="nudge-message"
                  >
                    Nudged 👀
                  </Text>
                ) : null}
              </>
            )}
          </View>
        </Card>
      )}

      <Button label="Sign out" variant="ghost" onPress={() => signOut.mutate()} testID="sign-out" />
    </ScrollView>
  );
}
