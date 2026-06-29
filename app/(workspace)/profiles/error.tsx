'use client';

type ProfilesErrorProps = {
  error: Error;
  reset: () => void;
};

export default function ProfilesError({ error, reset }: ProfilesErrorProps) {
  return (
    <main role="alert">
      <p>{error.message}</p>
      <button type="button" onClick={reset}>Retry</button>
    </main>
  );
}
