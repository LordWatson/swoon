import { PlaceholderScreen } from '../../features/accounts/PlaceholderScreen';

export default function MomentsScreen() {
  return (
    <PlaceholderScreen
      testID="tab-moments"
      overline="Your shared shoebox"
      title="Moments"
      emoji="📸"
      message="No moments yet. Go on, bottle one."
      actionLabel="Add a moment"
    />
  );
}
