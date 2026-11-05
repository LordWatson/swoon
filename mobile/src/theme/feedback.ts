/**
 * Press feedback: the spring, the scale and the haptic, in one place.
 *
 * Every pressable control in the app uses this, so how a press feels is a
 * single decision rather than eight. It also means "reduce motion" and the
 * haptic weight are honoured once, not remembered per component
 * (docs/UX_RULES.md rule 12).
 *
 * The haptic matters more than it looks on paper: on a real device a soft tap
 * under the thumb is most of the difference between an app that feels
 * expensive and one that feels like a web page. See docs/DECISIONS.md ADR-019.
 *
 * Uses React Native's own `Animated` rather than Reanimated. Both are here and
 * either would work, but mutating a Reanimated shared value inside an event
 * handler trips the React Compiler's `react-hooks/immutability` rule, and a
 * lint suppression in the one hook every control depends on is a bad trade for
 * a transform that `useNativeDriver` already runs off the JS thread. Reanimated
 * stays for the bigger set pieces — the reveal, the confetti.
 */
import * as Haptics from 'expo-haptics';
import { useState } from 'react';
import { Animated } from 'react-native';

import { durations, pressScale, springs } from './motion';
import { useTheme } from './index';

export type PressWeight = keyof typeof pressScale;

/** What a press should feel like. `celebrate` is for real wins only. */
export type HapticWeight = 'light' | 'medium' | 'celebrate' | 'none';

const HAPTIC_STYLE = {
  light: Haptics.ImpactFeedbackStyle.Light,
  medium: Haptics.ImpactFeedbackStyle.Medium,
} as const;

/**
 * Fire a haptic without ever letting it break the interaction.
 *
 * Haptics are unavailable on a simulator, on some Android hardware and under
 * iOS Low Power Mode, and the call rejects rather than no-ops. A button that
 * throws because the phone cannot buzz would be a genuinely silly bug.
 */
export function haptic(weight: HapticWeight): void {
  if (weight === 'none') return;

  const run =
    weight === 'celebrate'
      ? Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success)
      : Haptics.impactAsync(HAPTIC_STYLE[weight]);

  void run.catch(() => undefined);
}

export type PressFeedback = {
  /** Spread onto an Animated.View — or an Animated.createAnimatedComponent. */
  animatedStyle: { transform: [{ scale: Animated.Value }] };
  onPressIn: () => void;
  onPressOut: () => void;
};

/**
 * Spring-scale a control on press, with a haptic on the way down.
 *
 * With "reduce motion" on, the scale becomes a 100 ms timing curve of the same
 * magnitude rather than a spring — the control still responds, it just does
 * not bounce.
 */
export function usePressFeedback(
  weight: PressWeight = 'button',
  hapticWeight: HapticWeight = 'light',
): PressFeedback {
  const { reduceMotion } = useTheme();
  // useState with an initialiser, not useRef().current: the value must be
  // created once and survive re-renders, and reading a ref during render is
  // exactly what React 19's rules forbid.
  const [scale] = useState(() => new Animated.Value(1));

  const animate = (to: number): void => {
    const animation = reduceMotion
      ? Animated.timing(scale, {
          toValue: to,
          duration: durations.instant,
          useNativeDriver: true,
        })
      : Animated.spring(scale, {
          toValue: to,
          damping: springs.press.damping,
          stiffness: springs.press.stiffness,
          mass: springs.press.mass,
          useNativeDriver: true,
        });

    animation.start();
  };

  return {
    animatedStyle: { transform: [{ scale }] },
    onPressIn: () => {
      haptic(hapticWeight);
      animate(pressScale[weight]);
    },
    onPressOut: () => animate(1),
  };
}
