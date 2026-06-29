'use client';

import { FormEvent, useState } from 'react';
import { createConnection, searchExpertDirectory } from './api';
import type { ApiError, ExpertDirectoryResult } from './types';

export function ExpertDirectoryWorkspace() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<ExpertDirectoryResult[]>([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  const [actionMessage, setActionMessage] = useState<string | null>(null);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setSearched(true);
    setError(null);
    setActionMessage(null);

    try {
      setResults(await searchExpertDirectory(query));
    } catch (apiError) {
      setError(apiError as ApiError);
    } finally {
      setLoading(false);
    }
  }

  async function connect(result: ExpertDirectoryResult) {
    setError(null);
    setActionMessage(null);

    try {
      await createConnection(result.indexable_id, 'engineer_to_engineer');
      setActionMessage('Connection request sent.');
    } catch (apiError) {
      setError(apiError as ApiError);
    }
  }

  return (
    <main>
      <h1>Expert Directory</h1>

      <form onSubmit={submit}>
        <label>
          Search experts
          <input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Skills, company, plant, keywords"
          />
        </label>
        <button type="submit" disabled={loading}>{loading ? 'Searching...' : 'Search'}</button>
      </form>

      {error?.status === 401 || error?.status === 403 ? <p role="alert">You are not authorized to view these results.</p> : null}
      {error && error.status !== 401 && error.status !== 403 ? <p role="alert">{error.message}</p> : null}
      {actionMessage ? <p role="status">{actionMessage}</p> : null}
      {searched && !loading && results.length === 0 ? <p>No discoverable profiles found.</p> : null}

      <ul>
        {results.filter((result) => result.is_discoverable).map((result) => (
          <li key={result.id}>
            <h2>{result.structured_data.display_name ?? 'Profile'}</h2>
            <p>{result.structured_data.role_label ?? result.search_context}</p>
            <p>{result.searchable_text}</p>
            <button type="button" onClick={() => connect(result)}>Connect</button>
          </li>
        ))}
      </ul>
    </main>
  );
}
