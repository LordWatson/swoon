import { useRouter } from 'expo-router';
import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { AvatarPair, Button, Card, Hero, Stat } from '../../components';
import { daysTogether, firstName, formatCount, greeting } from '../../features/accounts/home';
import { useMe } from '../../features/accounts/useAuth';
import { partnerColourToken, useTheme } from '../../theme';

/**
 * Home.
 *
 * Everything on this screen comes from `/api/me`. It used to be hardcoded —
 * "Alex & Sam", 214 days together, a dinner booking, three AI date ideas — as
 * a showcase for the design language, which was harmless while nobody could
 * sign in. The moment sign-in worked it became a real person being shown
 * somebody else's name and invented content as if it were theirs.
 *
 * So there is **nothing fabricated here**. What the product cannot answer yet
 * is not shown: moments arrive in Phase 4, the Daily Spark in Phase 6, date
 * plans in Phase 3, and each of those tabs already has its own empty state.
 * An empty Home is honest; a full one that is fiction is not.
 */
export default function HomeScreen() {
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();
  const router = useRouter();
  const me = useMe();

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
      testID="tab-home"
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxxl * 2,
        gap: spacing.lg,
      }}
      showsVerticalScrollIndicator={false}
    >
      <Hero
        overline={greeting()}
        title={firstName(user?.name)}
        // No partner, no accent line — rather than a dangling ampersand.
        accent={partner === null ? undefined : `& ${firstName(partner.name)}`}
      >
        <View style={{ flexDirection: 'row', alignItems: 'flex-end', gap: spacing.xl }}>
          {days === null ? null : (
            <Stat
              value={formatCount(days)}
              label={days === 1 ? 'day together' : 'days together'}
              tone="feature"
              accent
            />
          )}

          {user !== undefined && partner !== null ? (
            <View style={{ flex: 1, alignItems: 'flex-end' }}>
              <AvatarPair
                tone="feature"
                partners={[
                  { name: user.name, colour: partnerColourToken(user.colour), done: true },
                  {
                    name: partner.name,
                    colour: partnerColourToken(partner.colour, 'ai'),
                    done: true,
                  },
                ]}
              />
            </View>
          ) : null}
        </View>
      </Hero>

      <Card overline="Your space" title="Just the two of you" emoji="🔒">
        <Text style={bodyStyle}>
          Everything you put in here stays between you and {firstName(partner?.name)} 💞
        </Text>
        <Text style={captionStyle}>No feed, no followers, and nobody else can see any of it.</Text>
      </Card>

      {/*
        What is coming, named honestly rather than mocked up. Each of these
        lands in its own phase and takes over its own tab; this card goes when
        the first of them arrives.
      */}
      <Card overline="Coming soon" title="What we are building next" emoji="🌱">
        <View style={{ gap: spacing.sm }}>
          <Text style={bodyStyle}>🍷 Date plans, cooked up for the pair of you</Text>
          <Text style={bodyStyle}>📸 Moments, your shared shoebox</Text>
          <Text style={bodyStyle}>🔥 A daily spark, one question at a time</Text>
        </View>
      </Card>

      {me.isError ? (
        <Card overline="Connection" title="We cannot reach us" emoji="📡">
          <Text style={bodyStyle}>Your space is still here. Have another go in a moment 💛</Text>
          <Button label="Try again" variant="ghost" onPress={() => void me.refetch()} />
        </Card>
      ) : null}

      <View style={{ gap: spacing.md, marginTop: spacing.sm }}>
        {/*
          Goes to the Dates tab, which has its own empty state, rather than a
          planner that does not exist yet. A button that does nothing is worse
          than one that is honest about where it leads.
        */}
        <Button
          label="Plan a date"
          emoji="🍷"
          size="large"
          onPress={() => router.push('/(tabs)/dates')}
          testID="home-plan-a-date"
        />
      </View>
    </ScrollView>
  );
}
