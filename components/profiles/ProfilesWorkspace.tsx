'use client';

import { useEffect, useState } from 'react';
import { acceptConnection, blockConnection, declineConnection, listConnections } from './api';
import { ProfileEditor } from './ProfileEditor';
import type { ApiError, ConnectionRecord, ProfileKind } from './types';

export function ProfilesWorkspace() {
  const [kind, setKind] = useState<ProfileKind>('engineer');
  const [connections, setConnections] = useState<ConnectionRecord[]>([]);
  const [error, setError] = useState<ApiError | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    listConnections()
      .then(setConnections)
      .catch((apiError: ApiError) => setError(apiError))
      .finally(() => setLoading(false));
  }, []);

  async function act(id: number, action: 'accept' | 'decline' | 'block') {
    setError(null);

    try {
      const updated = action === 'accept'
        ? await acceptConnection(id)
        : action === 'decline'
          ? await declineConnection(id)
          : await blockConnection(id);

      setConnections((items) => items.map((item) => (item.id === id ? updated : item)));
    } catch (apiError) {
      setError(apiError as ApiError);
    }
  }

  return (
    <main>
      <h1>Profiles</h1>

      <nav aria-label="Profile type">
        <button type="button" aria-pressed={kind === 'engineer'} onClick={() => setKind('engineer')}>Professional</button>
        <button type="button" aria-pressed={kind === 'unverified'} onClick={() => setKind('unverified')}>Unverified Member</button>
      </nav>

      <ProfileEditor kind={kind} />

      <section>
        <h2>Connections</h2>
        {loading ? <p aria-busy="true">Loading connections...</p> : null}
        {error ? <p role="alert">{error.message}</p> : null}
        {!loading && connections.length === 0 ? <p>No connections yet.</p> : null}

        <ul>
          {connections.map((connection) => (
            <li key={connection.id}>
              <strong>{connection.status}</strong> {connection.initiated_context}
              {connection.status === 'pending' ? (
                <>
                  <button type="button" onClick={() => act(connection.id, 'accept')}>Accept</button>
                  <button type="button" onClick={() => act(connection.id, 'decline')}>Decline</button>
                </>
              ) : null}
              {connection.status !== 'blocked' ? (
                <button type="button" onClick={() => act(connection.id, 'block')}>Block</button>
              ) : null}
            </li>
          ))}
        </ul>
      </section>
    </main>
  );
}
