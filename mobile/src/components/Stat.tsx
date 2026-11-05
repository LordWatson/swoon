import { Text, View } from 'react-native';

import { useTheme, type CardTone } from '../theme';

export type StatProps = {
  /** The number, already formatted: "214", "1,204", "6 months". */
  value: string;
  /** What it counts, in sentence case: "days together", "moments". */
  label: string;
  /** Which plane it sits on, so the ink is right. */
  tone?: CardTone;
  /** Draws the value in Butter instead of the plane's ink. Feature plane only. */
  accent?: boolean;
};

/**
 * A big number with a tracked label under it.
 *
 * The editorial device that makes a hero feel like a cover rather than a
 * header: Fraunces for the figure, tracked Nunito underneath. It exists as a
 * component because it turns up on most screens — days together, moments
 * saved, weeks in a row — and a one-off styled View in a feature folder is a
 * bug (UX rule 15).
 */
export function Stat({ value, label, tone = 'paper', accent = false }: StatProps) {
  const { colours, scaleType } = useTheme();

  const feature = tone === 'feature';
  const figure = scaleType('title');
  const overline = scaleType('overline');

  const valueColour = accent ? colours.featureAccent : feature ? colours.onFeature : colours.ink;

  return (
    <View accessible accessibilityLabel={`${value} ${label}`}>
      <Text
        style={{
          color: valueColour,
          fontSize: figure.size,
          lineHeight: figure.lineHeight,
          fontFamily: figure.family,
          letterSpacing: figure.letterSpacing,
        }}
      >
        {value}
      </Text>
      <Text
        style={{
          color: feature ? colours.featureMuted : colours.inkMuted,
          fontSize: overline.size,
          lineHeight: overline.lineHeight,
          fontFamily: overline.family,
          letterSpacing: overline.letterSpacing,
        }}
      >
        {label}
      </Text>
    </View>
  );
}
