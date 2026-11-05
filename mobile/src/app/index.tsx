import { useQuery } from '@tanstack/react-query';
import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { fetchHealth, type HealthResponse } from '../api/client';
import { AITag, AvatarPair, Button, Card, Chip, EmptyState, ProgressBar } from '../components';
import { API_BASE_URL, APP_NAME } from '../config/app';
import { moduleSignatures, useTheme } from '../theme';

/**
 * Placeholder Home screen for Phase 0.
 *
 * Two jobs: prove the API is reachable from the device, and show the theme and
 * component library rendering correctly in both colour schemes. Phase 1
 * replaces it with the real Home (On This Day, Daily Spark, next up,
 * challenge) behind the tab navigator.
 */
export default function HomeScreen() {
  const { colours, spacing, screenGutter, scaleType } = useTheme();
  const insets = useSafeAreaInsets();

  const health = useQuery<HealthResponse>({
    queryKey: ['health'],
    queryFn: ({ signal }) => fetchHealth(signal),
  });

  const display = scaleType('display');
  const body = scaleType('body');
  const caption = scaleType('caption');

  return (
    <ScrollView
      contentContainerStyle={{
        padding: screenGutter,
        paddingTop: insets.top + spacing.lg,
        paddingBottom: insets.bottom + spacing.xxl,
        gap: spacing.base,
      }}
      style={{ backgroundColor: colours.background }}
    >
      <Text
        style={{
          color: colours.ink,
          fontSize: display.size,
          lineHeight: display.lineHeight,
          fontFamily: display.family,
        }}
      >
        {APP_NAME}
      </Text>

      <Text
        style={{
          color: colours.inkMuted,
          fontSize: body.size,
          lineHeight: body.lineHeight,
          fontFamily: body.family,
        }}
      >
        Phase 0 scaffold. You two are not in here yet 💞
      </Text>

      <Card title="API health" emoji="🩺">
        {health.isPending ? (
          <Text style={{ color: colours.inkMuted, fontSize: body.size, fontFamily: body.family }}>
            Checking…
          </Text>
        ) : health.isError ? (
          <View style={{ gap: spacing.sm }}>
            <Text
              style={{ color: colours.dangerText, fontSize: body.size, fontFamily: body.family }}
            >
              Could not reach the API.
            </Text>
            <Text
              style={{
                color: colours.inkMuted,
                fontSize: caption.size,
                lineHeight: caption.lineHeight,
                fontFamily: caption.family,
              }}
            >
              {API_BASE_URL} — a phone or emulator cannot resolve a .test domain. See
              docs/DEV_TOOLING.md.
            </Text>
            <Button label="Try again" variant="ghost" onPress={() => health.refetch()} />
          </View>
        ) : (
          <View style={{ gap: spacing.xs }}>
            <Text
              style={{ color: colours.successText, fontSize: body.size, fontFamily: body.family }}
            >
              Connected to {health.data.app} ✅
            </Text>
            <Text
              style={{
                color: colours.inkMuted,
                fontSize: caption.size,
                lineHeight: caption.lineHeight,
                fontFamily: caption.family,
              }}
            >
              {health.data.environment} · {API_BASE_URL}
            </Text>
          </View>
        )}
      </Card>

      <Card title="Theme check" emoji="🎨">
        <View style={{ gap: spacing.md }}>
          <AITag label="AI" />

          <AvatarPair
            partners={[
              { name: 'Alex', colour: 'primary', done: true },
              { name: 'Sam', colour: 'ai', done: false },
            ]}
            showPending
          />

          <ProgressBar step={1} total={2} label="Answers" />

          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
            {(['date-planner', 'moments', 'daily-spark'] as const).map((key) => (
              <Chip
                key={key}
                label={moduleSignatures[key].label}
                emoji={moduleSignatures[key].emoji}
                selected={key === 'date-planner'}
              />
            ))}
          </View>

          <Button label="Primary action" onPress={() => undefined} emoji="🍷" />
          <Button label="Secondary" variant="secondary" onPress={() => undefined} />
        </View>
      </Card>

      <Card title="Empty state" emoji="📸">
        <EmptyState
          emoji="📸"
          message="No moments yet. Go on, bottle one."
          actionLabel="Add a moment"
          onAction={() => undefined}
        />
      </Card>
    </ScrollView>
  );
}
