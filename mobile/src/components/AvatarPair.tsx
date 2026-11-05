import { Text, View } from 'react-native';

import { useTheme, type ColourTokens } from '../theme';

export type Partner = {
  name: string;
  /** Identity colour token (`primary` for A, `ai` for B by default). */
  colour: keyof ColourTokens;
  /** True once this partner has done the thing being waited on. */
  done?: boolean;
};

export type AvatarPairProps = {
  partners: [Partner, Partner];
  size?: number;
  /** Dims whoever has not acted yet, for waiting states (UX rule 4). */
  showPending?: boolean;
};

/**
 * Both partners, always (UX rule 4).
 *
 * The identity colour is **never** the only signal — each avatar carries an
 * initial and a screen-reader label, because Rosé and Lavender are not
 * distinguishable to many people with colour vision deficiency
 * (docs/BRAND.md § 3.5).
 */
export function AvatarPair({ partners, size = 36, showPending = false }: AvatarPairProps) {
  const { colours, scaleType } = useTheme();
  const type = scaleType('label');

  const label = showPending
    ? partners.map((p) => `${p.name}: ${p.done ? 'answered' : 'waiting'}`).join(', ')
    : `${partners[0].name} and ${partners[1].name}`;

  return (
    <View
      style={{ flexDirection: 'row' }}
      accessible
      accessibilityRole="image"
      accessibilityLabel={label}
    >
      {partners.map((partner, index) => (
        <View
          key={`${partner.name}-${index}`}
          style={{
            width: size,
            height: size,
            borderRadius: size / 2,
            backgroundColor: colours[partner.colour],
            borderWidth: 2,
            borderColor: colours.surface,
            alignItems: 'center',
            justifyContent: 'center',
            marginLeft: index === 0 ? 0 : -size / 3,
            opacity: showPending && !partner.done ? 0.45 : 1,
          }}
        >
          <Text
            style={{
              // Every partner colour is a light fill, so the plum knockout is
              // the one that meets AA against all of them.
              color: colours.onPrimary,
              fontSize: type.size,
              fontFamily: type.family,
            }}
          >
            {partner.name.charAt(0).toUpperCase()}
          </Text>
        </View>
      ))}
    </View>
  );
}
