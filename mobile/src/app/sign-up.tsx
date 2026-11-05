import { AuthForm } from '../features/accounts/AuthForm';
import { useRegister } from '../features/accounts/useAuth';

/**
 * Sign up.
 *
 * The timezone is detected and sent rather than asked for: everything the app
 * schedules is rendered in the user's own time, and making that a question
 * would be a worse first impression than getting it right silently. P1.T06
 * lets them confirm it during onboarding.
 */
export default function SignUpScreen() {
  const register = useRegister();

  return (
    <AuthForm
      testID="screen-sign-up"
      mode="sign-up"
      overline="Let's begin"
      title="Make your space"
      submitLabel="Create account"
      busy={register.isPending}
      error={register.error}
      onSubmit={({ name, email, password }) =>
        register.mutate({ name, email, password, timezone: detectTimezone() })
      }
    />
  );
}

/**
 * The device's timezone, from `Intl` rather than a dependency.
 *
 * `expo-localization` would be a package for one line that the platform
 * already answers. If Intl is unavailable or returns nothing useful, the field
 * is simply omitted and the server's default stands — the API treats the
 * timezone as optional for exactly this reason.
 */
function detectTimezone(): string | undefined {
  try {
    const zone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    return zone === '' ? undefined : zone;
  } catch {
    return undefined;
  }
}
