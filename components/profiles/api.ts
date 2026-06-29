import type { ApiError, ConnectionRecord, ExpertDirectoryResult, ProfileForm, ProfileKind, PublicProfile } from './types';

const JSON_HEADERS = {
  Accept: 'application/json',
  'Content-Type': 'application/json',
};

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`/api/v1${path}`, {
    ...init,
    credentials: 'include',
    headers: {
      ...JSON_HEADERS,
      ...init.headers,
    },
  });

  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    const error: ApiError = {
      message: payload.message ?? 'Request failed.',
      errors: payload.errors,
      status: response.status,
    };

    throw error;
  }

  return payload as T;
}

export function getMyProfile(kind: ProfileKind): Promise<ProfileForm | null> {
  return request<ProfileForm | null>(kind === 'engineer' ? '/profile/engineer' : '/profile/unverified');
}

export function saveMyProfile(kind: ProfileKind, data: ProfileForm): Promise<ProfileForm> {
  return request<ProfileForm>(kind === 'engineer' ? '/profile/engineer' : '/profile/unverified', {
    method: 'PUT',
    body: JSON.stringify(data),
  });
}

export function getPublicProfile(kind: ProfileKind, id: number): Promise<PublicProfile> {
  const path = kind === 'engineer' ? `/profiles/engineers/${id}` : `/profiles/unverified-members/${id}`;

  return request<PublicProfile>(path);
}

export function searchExpertDirectory(query: string): Promise<ExpertDirectoryResult[]> {
  const params = new URLSearchParams();

  if (query.trim() !== '') {
    params.set('q', query.trim());
  }

  const suffix = params.toString() === '' ? '' : `?${params.toString()}`;

  return request<ExpertDirectoryResult[]>(`/expert-directory${suffix}`);
}

export function listConnections(): Promise<ConnectionRecord[]> {
  return request<ConnectionRecord[]>('/connections');
}

export function createConnection(receiverId: number, initiatedContext: ConnectionRecord['initiated_context']): Promise<ConnectionRecord> {
  return request<ConnectionRecord>('/connections', {
    method: 'POST',
    body: JSON.stringify({
      receiver_id: receiverId,
      initiated_context: initiatedContext,
    }),
  });
}

export function acceptConnection(id: number): Promise<ConnectionRecord> {
  return request<ConnectionRecord>(`/connections/${id}/accept`, { method: 'POST' });
}

export function declineConnection(id: number): Promise<ConnectionRecord> {
  return request<ConnectionRecord>(`/connections/${id}/decline`, { method: 'POST' });
}

export function blockConnection(id: number): Promise<ConnectionRecord> {
  return request<ConnectionRecord>(`/connections/${id}/block`, { method: 'POST' });
}
