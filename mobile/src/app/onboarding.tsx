import { useRouter } from 'expo-router';
import { useState } from 'react';
import { ScrollView, Text, TextInput, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button, Chip, Hero, ProgressBar } from '../components';
import {
  ACCESSIBILITY_CHOICES,
  DIETARY_CHOICES,
  isSkippable,
  nextStep,
  ONBOARDING_STEPS,
  previousStep,
  stepNumber,
  toggle,
  type OnboardingDraft,
  type OnboardingStep,
} from '../features/accounts/onboarding';
import { useMe, useUpdateProfile } from '../features/accounts/useAuth';
import { useOnboardingDraft } from '../features/accounts/useOnboardingDraft';
import { useTheme } from '../theme';

/**
 * Onboarding: four questions, one per screen.
 *
 * **One question per screen** (UX rule 3) — the step drives what is rendered,
 * and the list in `onboarding.ts` is the order. It is one component rather than
 * four routes because the four screens share a draft, a progress bar and a
 * back/next pair; four routes would mean handing the draft between them, which
 * is exactly the state that gets lost.
 *
 * Each answer is PATCHed as it is given, so giving up halfway keeps whatever
 * was reached. The draft in secure storage exists only so re-opening the app
 * does not blank the screen you were on.
 */
export default function OnboardingScreen() {
  const { colours, radii, spacing, screenGutter, minTouchTarget, scaleType } = useTheme();
  const insets = useSafeAreaInsets();
  const router = useRouter();
  const me = useMe();
  const save = useUpdateProfile();
  const { draft, update, clear, isReady } = useOnboardingDraft();

  const [step, setStep] = useState<OnboardingStep>('name');

  const body = scaleType('body');
  const caption = scaleType('caption');

  if (!isReady || draft === null) return null;

  // The draft wins over the server copy: it is what the person last typed.
  const name = draft.name ?? me.data?.user.name ?? '';
  const timezone = draft.timezone ?? me.data?.user.timezone ?? detectTimezone() ?? 'Europe/London';
  const dietary = draft.dietary_needs ?? me.data?.user.dietary_needs ?? [];
  const accessibility = draft.accessibility_needs ?? me.data?.user.accessibility_needs ?? [];

  const counter = `Question ${stepNumber(step)} of ${ONBOARDING_STEPS.length}`;
  const back = previousStep(step);

  function advance(patch: OnboardingDraft) {
    if (Object.keys(patch).length > 0) {
      save.mutate(patch);
    }

    const next = nextStep(step);

    if (next === null) {
      clear();
      router.replace('/invite');

      return;
    }

    setStep(next);
  }

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
      testID="screen-onboarding"
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxl,
        gap: spacing.lg,
      }}
      keyboardShouldPersistTaps="handled"
    >
      <ProgressBar step={stepNumber(step)} total={ONBOARDING_STEPS.length} label="Setting up" />

      {step === 'name' ? (
        <>
          <Hero overline={counter} title="What shall we call you?" />
          <TextInput
            testID="onboarding-name"
            accessibilityLabel="Your name"
            value={name}
            onChangeText={(value) => update({ name: value })}
            autoCapitalize="words"
            style={{
              minHeight: minTouchTarget,
              borderWidth: 1.5,
              borderColor: colours.borderStrong,
              borderRadius: radii.input,
              paddingHorizontal: spacing.base,
              color: colours.ink,
              fontSize: body.size,
              fontFamily: body.family,
              backgroundColor: colours.surface,
            }}
          />
        </>
      ) : null}

      {step === 'timezone' ? (
        <>
          <Hero overline={counter} title="Are we in the right place?" />
          <Text style={bodyStyle}>
            We will schedule everything in your time. We think you are in {timezone} 🌍
          </Text>
          <Text style={captionStyle}>You can change this in settings whenever you travel.</Text>
        </>
      ) : null}

      {step === 'dietary' ? (
        <>
          <Hero overline={counter} title="Anything you would rather not eat?" />
          <Text style={bodyStyle}>We will keep it in mind when we suggest somewhere 🍽️</Text>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
            {DIETARY_CHOICES.map((choice) => (
              <Chip
                key={choice}
                label={choice}
                selected={dietary.includes(choice)}
                onPress={() => update({ dietary_needs: toggle(dietary, choice) })}
                testID={`dietary-${choice}`}
              />
            ))}
          </View>
        </>
      ) : null}

      {step === 'accessibility' ? (
        <>
          <Hero overline={counter} title="Anything that makes a place easier?" />
          <Text style={bodyStyle}>We will only suggest places that work for you two ♿</Text>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
            {ACCESSIBILITY_CHOICES.map((choice) => (
              <Chip
                key={choice}
                label={choice}
                selected={accessibility.includes(choice)}
                onPress={() => update({ accessibility_needs: toggle(accessibility, choice) })}
                testID={`accessibility-${choice}`}
              />
            ))}
          </View>
        </>
      ) : null}

      <View style={{ gap: spacing.md, marginTop: spacing.base }}>
        <Button
          label={nextStep(step) === null ? 'All done' : 'Next'}
          size="large"
          onPress={() => advance(patchFor(step, { name, timezone, dietary, accessibility }))}
          testID="onboarding-next"
        />

        {/* Every non-essential question has a way to say nothing (UX rule 3). */}
        {isSkippable(step) ? (
          <Button
            label="No preference"
            variant="ghost"
            onPress={() => advance({})}
            testID="onboarding-skip"
          />
        ) : null}

        {back !== null ? (
          <Button
            label="Back"
            variant="ghost"
            onPress={() => setStep(back)}
            testID="onboarding-back"
          />
        ) : null}
      </View>
    </ScrollView>
  );
}

/** What this step saves. Each screen PATCHes only its own answer. */
function patchFor(
  step: OnboardingStep,
  values: { name: string; timezone: string; dietary: string[]; accessibility: string[] },
): OnboardingDraft {
  switch (step) {
    case 'name':
      return { name: values.name };
    case 'timezone':
      return { timezone: values.timezone };
    case 'dietary':
      return { dietary_needs: values.dietary };
    case 'accessibility':
      return { accessibility_needs: values.accessibility };
  }
}

function detectTimezone(): string | undefined {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || undefined;
  } catch {
    return undefined;
  }
}
