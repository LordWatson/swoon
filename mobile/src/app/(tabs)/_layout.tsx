import { Tabs } from 'expo-router';
import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';

import { QuickAddSheet, type QuickAddOption } from '../../features/accounts/QuickAddSheet';
import { useTheme } from '../../theme';

/**
 * The five tabs a coupled user sees: Home · Dates · Moments · Planner · Us.
 *
 * **No hamburger menu, ever** (UX rule 14). Five tabs and a centre "+" is the
 * whole navigation: everything is one tap from anywhere, and every deep link
 * resolves to a tab with a back path rather than a modal with no way out.
 */
export default function TabsLayout() {
  const { colours, spacing, scaleType, minTouchTarget } = useTheme();
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
        <Tabs.Screen name="index" options={{ title: 'Home', tabBarLabel: 'Home' }} />
        <Tabs.Screen name="dates" options={{ title: 'Dates', tabBarLabel: 'Dates' }} />
        <Tabs.Screen name="moments" options={{ title: 'Moments', tabBarLabel: 'Moments' }} />
        <Tabs.Screen name="planner" options={{ title: 'Planner', tabBarLabel: 'Planner' }} />
        <Tabs.Screen name="us" options={{ title: 'Us', tabBarLabel: 'Us' }} />
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
          <Text style={{ color: colours.onPrimary, fontSize: 28, lineHeight: 32 }}>+</Text>
        </Pressable>
      </View>

      <QuickAddSheet
        visible={quickAddOpen}
        onClose={() => setQuickAddOpen(false)}
        onChoose={(option: QuickAddOption) => {
          setQuickAddOpen(false);
          // TODO P3/P4/P6: route to the creation screen for each option as
          // those modules land. Deliberately a no-op rather than a dead
          // navigation that would 404 the user.
          void option;
        }}
      />
    </>
  );
}
