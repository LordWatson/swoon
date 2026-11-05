import { useQuery } from '@tanstack/react-query';
import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { fetchHealth, type HealthResponse } from '../api/client';
import {
  AITag,
  AvatarPair,
  Button,
  Card,
  Chip,
  EmptyState,
  Hero,
  ProgressBar,
  Stat,
} from '../components';
import { API_BASE_URL, APP_NAME } from '../config/app';
import { moduleSignatures, useTheme } from '../theme';

/**
 * Placeholder Home screen for Phase 0.
 *
 * Two jobs: prove the API is reachable from the device, and show the design
 * language rendering correctly in both colour schemes. Phase 1 replaces it
 * with the real Home (On This Day, Daily Spark, next up, challenge) behind the
 * tab navigator.
 *
 * The layout is the one the design language is built around: a plum feature
 * hero, then paper cards on cream, each led by a tracked overline. One hero,
 * one primary action, everything else quiet.
 */
export default function HomeScreen() {
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();

  const health = useQuery<HealthResponse>({
    queryKey: ['health'],
    queryFn: ({ signal }) => fetchHealth(signal),
  });

  const body = scaleType('body');
  const caption = scaleType('caption');

  const bodyStyle = {
    color: colours.ink,
    fontSize: body.size,
    lineHeight: body.lineHeight,
    fontFamily: body.family,
    letterSpacing: body.letterSpacing,
  };

  const mutedCaption = {
    color: colours.inkMuted,
    fontSize: caption.size,
    lineHeight: caption.lineHeight,
    fontFamily: caption.family,
    letterSpacing: caption.letterSpacing,
  };

  return (
    <ScrollView
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxxl,
        gap: spacing.lg,
      }}
      style={{ backgroundColor: colours.background }}
      showsVerticalScrollIndicator={false}
    >
      <Hero overline="Good evening," title="Alex" accent="& Sam">
        <View style={{ flexDirection: 'row', alignItems: 'flex-end', gap: spacing.xl }}>
          <Stat value="214" label="days together" tone="feature" accent />
          <Stat value="31" label="moments saved" tone="feature" />
          <View style={{ flex: 1, alignItems: 'flex-end' }}>
            <AvatarPair
              partners={[
                { name: 'Alex', colour: 'primary', done: true },
                { name: 'Sam', colour: 'ai', done: true },
              ]}
              tone="feature"
            />
          </View>
        </View>
      </Hero>

      <Card overline="This Friday" title="Dinner at Oklava" emoji="🍷">
        <Text style={bodyStyle}>
          Booked for 7.30pm. We&apos;ll nudge you both the day before 🎉
        </Text>
      </Card>

      <Card overline="Cooked up for you two" title="Three date ideas" emoji="✨">
        <View style={{ gap: spacing.md }}>
          <AITag label="Date ideas" />
          <Text style={bodyStyle}>
            A late-night bakery crawl, a pottery class, and the rooftop cinema you saved in March ✨
          </Text>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
            {(['date-planner', 'moments', 'daily-spark'] as const).map((key, index) => (
              <Chip
                key={key}
                label={moduleSignatures[key].label}
                emoji={moduleSignatures[key].emoji}
                selected={index === 0}
                onPress={() => undefined}
              />
            ))}
          </View>
        </View>
      </Card>

      <Card overline="Tonight's spark" title="What made you laugh this week?" emoji="🔥">
        <View style={{ gap: spacing.md }}>
          <Text style={mutedCaption}>Waiting for Sam to answer 👀</Text>
          <ProgressBar step={1} total={2} label="Answers" />
        </View>
      </Card>

      <Card overline="Connection" title="API health" emoji="🩺" testID="health-card">
        {health.isPending ? (
          <Text style={mutedCaption}>Checking…</Text>
        ) : health.isError ? (
          <View style={{ gap: spacing.md }}>
            <Text style={{ ...bodyStyle, color: colours.dangerText }}>
              Could not reach the API.
            </Text>
            <Text style={mutedCaption}>
              {API_BASE_URL} — a phone or emulator cannot resolve a .test domain. See
              docs/DEV_TOOLING.md.
            </Text>
            <Button label="Try again" variant="ghost" onPress={() => health.refetch()} />
          </View>
        ) : (
          <View style={{ gap: spacing.xs }}>
            <Text style={{ ...bodyStyle, color: colours.successText }}>
              Connected to {health.data.app} ✅
            </Text>
            <Text style={mutedCaption}>
              {health.data.environment} · {API_BASE_URL}
            </Text>
          </View>
        )}
      </Card>

      <Card>
        <EmptyState
          emoji="📸"
          message="No moments yet. Go on, bottle one."
          actionLabel="Add a moment"
          onAction={() => undefined}
        />
      </Card>

      <View style={{ gap: spacing.md, marginTop: spacing.sm }}>
        <Button label="Plan a date" emoji="🍷" size="large" onPress={() => undefined} />
        <Text style={{ ...mutedCaption, textAlign: 'center' }}>
          {APP_NAME} · Phase 0 scaffold, and you two are not in here yet 💞
        </Text>
      </View>
    </ScrollView>
  );
}
