/**
 * A two-stop vertical gradient, sized to its parent.
 *
 * Not exported from `src/components` — it is an implementation detail of
 * `Button` and `Card`, not a thing screens should reach for. React Native has
 * no gradient of its own, and `expo-linear-gradient` would be a new dependency
 * for what `react-native-svg` (already here, for Phosphor icons) does in nine
 * lines.
 *
 * Why a gradient at all: a flat fill has no sense of light falling on it. One
 * subtle step from a lighter top to the base colour is the difference between
 * a coloured rectangle and a surface. Both ends are theme tokens, so both are
 * covered by the contrast gate.
 */
import { StyleSheet } from 'react-native';
import Svg, { Defs, LinearGradient, Rect, Stop } from 'react-native-svg';

export type GradientFillProps = {
  /** Top of the gradient — the lighter end. */
  from: string;
  /** Bottom of the gradient — the base token. */
  to: string;
  /** Matches the parent's borderRadius, or the corners show through. */
  radius: number;
};

export function GradientFill({ from, to, radius }: GradientFillProps) {
  return (
    <Svg style={StyleSheet.absoluteFill} pointerEvents="none">
      <Defs>
        <LinearGradient id="fill" x1="0" y1="0" x2="0" y2="1">
          <Stop offset="0" stopColor={from} />
          <Stop offset="1" stopColor={to} />
        </LinearGradient>
      </Defs>
      <Rect x="0" y="0" width="100%" height="100%" rx={radius} ry={radius} fill="url(#fill)" />
    </Svg>
  );
}
