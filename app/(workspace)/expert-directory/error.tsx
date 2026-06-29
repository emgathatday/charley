'use client';

type ExpertDirectoryErrorProps = {
  error: Error;
  reset: () => void;
};

export default function ExpertDirectoryError({ error, reset }: ExpertDirectoryErrorProps) {
  return (
    <main role="alert">
      <p>{error.message}</p>
      <button type="button" onClick={reset}>Retry</button>
    </main>
  );
}
