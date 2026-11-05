import { AuthForm } from '../features/accounts/AuthForm';
import { useSignIn } from '../features/accounts/useAuth';

export default function SignInScreen() {
  const signIn = useSignIn();

  return (
    <AuthForm
      testID="screen-sign-in"
      mode="sign-in"
      overline="Welcome back"
      title="Sign in"
      submitLabel="Sign in"
      busy={signIn.isPending}
      error={signIn.error}
      onSubmit={({ email, password }) => signIn.mutate({ email, password })}
    />
  );
}
