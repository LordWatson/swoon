import { Tabs, useRouter } from 'expo-router';
import { Calendar, Camera, Heart, House, Plus, Wine } from 'phosphor-react-native';
import { useState } from 'react';
import { Pressable, View, type ColorValue } from 'react-native';

import { QuickAddSheet, type QuickAddOption } from '../../features/accounts/QuickAddSheet';
import { useTheme } from '../../theme';

/**
 * One icon per tab.
 *
 * **Icons, not emoji.** Emoji are content — a moment's mood, a module's
 * signature mark — and icons are UI (docs/BRAND.md § 7). A tab bar is UI.
 *
 * Phosphor at `bold`, which § 7 specifies for inline UI, and the same weight
 * across every tab: mixing weights within a screen is explicitly out. The
 * active state is carried by colour alone, which is also why each tab keeps
 * its text label — colour is never the only signal.
 */
const TAB_ICONS = {
  index: House,
  dates: Wine,
  moments: Camera,
  planner: Calendar,
  us: Heart,
} as const;

type TabName = keyof typeof TAB_ICONS;

function tabIcon(name: TabName) {
  const Icon = TAB_ICONS[name];

  // React Navigation types `color` as a ColorValue, which includes null; the
  // tab's own tint is always set, so the string check covers a case that
  // cannot happen without widening the icon's own prop type.
  const TabBarIcon = ({ color, size }: { color: ColorValue; size: number }) => (
    <Icon color={typeof color === 'string' ? color : undefined} size={size} weight="bold" />
  );

  // Named, so React DevTools and the lint rule both have something to call it.
  TabBarIcon.displayName = `TabBarIcon(${name})`;

  return TabBarIcon;
}

/**
 * The five tabs a coupled user sees: Home · Dates · Moments · Planner · Us.
 *
 * **No hamburger menu, ever** (UX rule 14). Five tabs and a centre "+" is the
 * whole navigation: everything is one tap from anywhere, and every deep link
 * resolves to a tab with a back path rather than a modal with no way out.
 */
/**
 * Where each quick-add option leads **today**.
 *
 * None of the four creation screens exists yet, so each option goes to the
 * tab that will own it — and that tab says plainly which phase brings it.
 * Closing the sheet and doing nothing, which is what it did before, is the
 * worst of the options: the person has no idea whether they tapped it wrong.
 */
const QUICK_ADD_DESTINATIONS = {
  moment: '/(tabs)/moments',
  plan: '/(tabs)/planner',
  note: '/(tabs)/us',
  'date-idea': '/(tabs)/dates',
} as const satisfies Record<QuickAddOption, string>;

export default function TabsLayout() {
  const { colours, spacing, scaleType, minTouchTarget } = useTheme();
  const router = useRouter();
  const [quickAddOpen, setQuickAddOpen] = useState(false);
  const label = scaleType('caption');

  return (
    <>
      <Tabs
        screenOptions={{
          headerShown: false,
          tabBarActiveTintColor: colours.primaryText,
          tabBarInactiveTintColor: colours.inkMuted,
          tabBarStyle: {
            backgroundColor: colours.surface,
            borderTopColor: colours.border,
          },
          tabBarLabelStyle: {
            fontFamily: label.family,
            fontSize: label.size,
            letterSpacing: label.letterSpacing,
          },
        }}
      >
        {/*
          Without a tabBarIcon, React Navigation draws a placeholder glyph —
          which is what rendered as an "X" in the bar.
        */}
        <Tabs.Screen
          name="index"
          options={{ title: 'Home', tabBarLabel: 'Home', tabBarIcon: tabIcon('index') }}
        />
        <Tabs.Screen
          name="dates"
          options={{ title: 'Dates', tabBarLabel: 'Dates', tabBarIcon: tabIcon('dates') }}
        />
        <Tabs.Screen
          name="moments"
          options={{ title: 'Moments', tabBarLabel: 'Moments', tabBarIcon: tabIcon('moments') }}
        />
        <Tabs.Screen
          name="planner"
          options={{ title: 'Planner', tabBarLabel: 'Planner', tabBarIcon: tabIcon('planner') }}
        />
        <Tabs.Screen
          name="us"
          options={{ title: 'Us', tabBarLabel: 'Us', tabBarIcon: tabIcon('us') }}
        />
      </Tabs>

      {/*
        The "+" floats above the tab bar rather than being a sixth tab: it is
        an action, not a place, and a tab that does not navigate confuses
        screen-reader users most of all. It carries a real label for the same
        reason — an icon-only control without one is an accessibility bug
        (UX rule 12).
      */}
      <View
        style={{
          position: 'absolute',
          bottom: spacing.xxxl + spacing.lg,
          alignSelf: 'center',
          alignItems: 'center',
        }}
        pointerEvents="box-none"
      >
        <Pressable
          onPress={() => setQuickAddOpen(true)}
          accessibilityRole="button"
          accessibilityLabel="Add something"
          testID="quick-add-open"
          style={{
            width: minTouchTarget + 12,
            height: minTouchTarget + 12,
            borderRadius: (minTouchTarget + 12) / 2,
            backgroundColor: colours.primary,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Plus color={colours.onPrimary} size={26} weight="bold" />
        </Pressable>
      </View>

      <QuickAddSheet
        visible={quickAddOpen}
        onClose={() => setQuickAddOpen(false)}
        onChoose={(option: QuickAddOption) => {
          setQuickAddOpen(false);
          // TODO P3/P4/P6: point these at the real creation screens as those
          // modules land. Until then each one lands on the tab that will own
          // it, which says which phase brings it.
          router.push(QUICK_ADD_DESTINATIONS[option]);
        }}
      />
    </>
  );
}
