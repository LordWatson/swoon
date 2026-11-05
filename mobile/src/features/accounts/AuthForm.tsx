import { useState } from 'react';
import { ScrollView, Text, TextInput, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError } from '../../api/client';
import { Button, Hero } from '../../components';
import { useTheme } from '../../theme';

export type AuthFormProps = {
  mode: 'sign-up' | 'sign-in';
  overline: string;
  title: string;
  submitLabel: string;
  busy: boolean;
  error: unknown;
  onSubmit: (values: { name: string; email: string; password: string }) => void;
  testID: string;
};

/**
 * Sign up and sign in, which are the same form with one extra field.
 *
 * Validation errors come from the server and are rendered **per field**, in
 * the server's own words: the API already speaks the brand voice (British,
 * warm, never scolding), so restating its messages in the client would mean
 * two copies to keep in step and one of them going stale.
 */
export function AuthForm({
  mode,
  overline,
  title,
  submitLabel,
  busy,
  error,
  onSubmit,
  testID,
}: AuthFormProps) {
  const { colours, radii, spacing, screenGutter, minTouchTarget, scaleType } = useTheme();
  const insets = useSafeAreaInsets();

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const label = scaleType('label');
  const body = scaleType('body');
  const caption = scaleType('caption');

  const fieldErrors = error instanceof ApiError ? (error.errors ?? {}) : {};
  const generalError =
    error instanceof ApiError && !error.isValidation
      ? error.isRetryable
        ? 'We could not reach us. Have another go in a moment 📡'
        : error.message
      : null;

  const inputStyle = {
    minHeight: minTouchTarget,
    borderWidth: 1.5,
    borderColor: colours.borderStrong,
    borderRadius: radii.input,
    paddingHorizontal: spacing.base,
    color: colours.ink,
    fontSize: body.size,
    fontFamily: body.family,
    backgroundColor: colours.surface,
  };

  const labelStyle = {
    color: colours.inkMuted,
    fontSize: label.size,
    lineHeight: label.lineHeight,
    fontFamily: label.family,
    letterSpacing: label.letterSpacing,
  };

  const errorStyle = {
    color: colours.dangerText,
    fontSize: caption.size,
    lineHeight: caption.lineHeight,
    fontFamily: caption.family,
  };

  return (
    <ScrollView
      testID={testID}
      style={{ backgroundColor: colours.background }}
      contentContainerStyle={{
        paddingHorizontal: screenGutter,
        paddingTop: insets.top + spacing.base,
        paddingBottom: insets.bottom + spacing.xxl,
        gap: spacing.lg,
      }}
      keyboardShouldPersistTaps="handled"
    >
      <Hero overline={overline} title={title} />

      {generalError ? (
        <Text style={errorStyle} testID="auth-error">
          {generalError}
        </Text>
      ) : null}

      {mode === 'sign-up' ? (
        <View style={{ gap: spacing.xs }}>
          <Text style={labelStyle} nativeID="field-name">
            What shall we call you?
          </Text>
          <TextInput
            accessibilityLabelledBy="field-name"
            accessibilityLabel="What shall we call you?"
            testID="field-name"
            value={name}
            onChangeText={setName}
            autoCapitalize="words"
            autoComplete="name"
            style={inputStyle}
          />
          {fieldErrors.name ? <Text style={errorStyle}>{fieldErrors.name[0]}</Text> : null}
        </View>
      ) : null}

      <View style={{ gap: spacing.xs }}>
        <Text style={labelStyle} nativeID="field-email">
          Email address
        </Text>
        <TextInput
          accessibilityLabelledBy="field-email"
          accessibilityLabel="Email address"
          testID="field-email"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          style={inputStyle}
        />
        {fieldErrors.email ? <Text style={errorStyle}>{fieldErrors.email[0]}</Text> : null}
      </View>

      <View style={{ gap: spacing.xs }}>
        <Text style={labelStyle} nativeID="field-password">
          Password
        </Text>
        <TextInput
          accessibilityLabelledBy="field-password"
          accessibilityLabel="Password"
          testID="field-password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoCapitalize="none"
          autoComplete={mode === 'sign-up' ? 'new-password' : 'current-password'}
          style={inputStyle}
        />
        {fieldErrors.password ? <Text style={errorStyle}>{fieldErrors.password[0]}</Text> : null}
        {mode === 'sign-up' ? (
          <Text style={{ ...labelStyle, fontSize: caption.size }}>
            Ten characters or more. A phrase you will remember beats a puzzle you will not 🔑
          </Text>
        ) : null}
      </View>

      <Button
        label={submitLabel}
        size="large"
        loading={busy}
        onPress={() => onSubmit({ name, email, password })}
        testID="auth-submit"
      />
    </ScrollView>
  );
}
