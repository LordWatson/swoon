import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { AvatarPair, Button, Card, Hero } from '../../components';
import { daysTogether, firstName, formatCount } from '../../features/accounts/home';
import { useMe, useSignOut } from '../../features/accounts/useAuth';
import { partnerColourToken, useTheme } from '../../theme';

/**
 * Us.
 *
 * Deliberately **live rather than a placeholder**, even though couple
 * settings, unlink and the privacy screen are all Phase 5, because of a gap
 * the audit turned up: sign-out existed only on the invite screen, which only
 * a solo user ever sees. A coupled person could not sign out at all — not a
 * missing feature, a trap.
 *
 * So this shows what we genuinely know about the couple, offers the one
 * account action that works today, and names what is coming without mocking
 * it up.
 */
export default function UsScreen() {
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();
  const me = useMe();
  const signOut = useSignOut();

  const body = scaleType('body');
  const caption = scaleType('caption');

  const user = me.data?.user;
  const couple = me.data?.couple ?? null;
  const partner = couple?.partner ?? null;
  const days = daysTogether(couple);

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

  return (
    <ScrollView
      testID="tab-us"
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxxl * 2,
        gap: spacing.lg,
      }}
    >
      <Hero
        overline="The pair of you"
        title={firstName(user?.name)}
        accent={partner === null ? undefined : `& ${firstName(partner.name)}`}
      >
        {user !== undefined && partner !== null ? (
          <AvatarPair
            tone="feature"
            partners={[
              { name: user.name, colour: partnerColourToken(user.colour), done: true },
              { name: partner.name, colour: partnerColourToken(partner.colour, 'ai'), done: true },
            ]}
          />
        ) : null}
      </Hero>

      {couple !== null ? (
        <Card overline="Your space" title="Since you linked" emoji="💞">
          <View style={{ gap: spacing.xs }}>
            {days === null ? null : (
              <Text style={bodyStyle}>
                {formatCount(days)} {days === 1 ? 'day' : 'days'} together 💛
              </Text>
            )}
            <Text style={captionStyle}>
              Linked on {new Date(couple.linked_at).toLocaleDateString('en-GB')}
            </Text>
          </View>
        </Card>
      ) : null}

      <Card overline="Coming soon" title="Settings and your data" emoji="🌱">
        <View style={{ gap: spacing.sm }}>
          <Text style={bodyStyle}>
            Couple settings, your anniversary dates, a copy of everything you have put in, and
            unlinking — all arriving in Phase 5 🗓️
          </Text>
          <Text style={captionStyle}>
            Unlinking already works on the API and needs no one&apos;s permission but yours. The
            screen for it is being written.
          </Text>
        </View>
      </Card>

      <Card overline="This device" title="Signing out" emoji="👋">
        <View style={{ gap: spacing.md }}>
          <Text style={captionStyle}>
            This signs you out here only. Your other devices stay signed in, and nothing is deleted.
          </Text>
          <Button
            label="Sign out"
            variant="ghost"
            loading={signOut.isPending}
            onPress={() => signOut.mutate()}
            testID="us-sign-out"
          />
        </View>
      </Card>
    </ScrollView>
  );
}
