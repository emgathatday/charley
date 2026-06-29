'use client';

import { FormEvent, useEffect, useState } from 'react';
import { getMyProfile, saveMyProfile } from './api';
import type { ApiError, ProfileForm, ProfileKind } from './types';

const emptyProfile: ProfileForm = {
  bio: '',
  experience_years: '',
  linkedin_url: '',
  job_availability: '',
  expertise_tags: [],
  searchable_keywords: [],
  is_discoverable: true,
  privacy_settings: {
    show_email: 'connections_only',
    show_phone: 'none',
    show_activity_feed: true,
    contact_visibility: 'connections_only',
  },
  notification_preferences: {
    connection_requests: true,
    profile_visibility: true,
    verification_reminders: true,
  },
};

type ProfileEditorProps = {
  kind: ProfileKind;
};

export function ProfileEditor({ kind }: ProfileEditorProps) {
  const [profile, setProfile] = useState<ProfileForm>(emptyProfile);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);

  useEffect(() => {
    let mounted = true;

    getMyProfile(kind)
      .then((data) => {
        if (mounted && data) {
          setProfile({ ...emptyProfile, ...data });
        }
      })
      .catch((apiError: ApiError) => {
        if (mounted) {
          setError(apiError);
        }
      })
      .finally(() => {
        if (mounted) {
          setLoading(false);
        }
      });

    return () => {
      mounted = false;
    };
  }, [kind]);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setSaved(false);
    setError(null);

    try {
      const updated = await saveMyProfile(kind, profile);
      setProfile({ ...emptyProfile, ...updated });
      setSaved(true);
    } catch (apiError) {
      setError(apiError as ApiError);
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <section aria-busy="true">Loading profile...</section>;
  }

  if (error?.status === 401 || error?.status === 403) {
    return <section role="alert">You are not authorized to edit this profile.</section>;
  }

  return (
    <form onSubmit={submit}>
      <h2>{kind === 'engineer' ? 'Professional profile' : 'Unverified member profile'}</h2>

      {error ? <p role="alert">{error.message}</p> : null}
      {saved ? <p role="status">Profile saved.</p> : null}

      <label>
        Bio
        <textarea
          value={profile.bio}
          onChange={(event) => setProfile({ ...profile, bio: event.target.value })}
        />
      </label>

      {kind === 'engineer' ? (
        <>
          <label>
            Current company
            <input
              value={profile.current_company ?? ''}
              onChange={(event) => setProfile({ ...profile, current_company: event.target.value })}
            />
          </label>
          <label>
            Position
            <input
              value={profile.position ?? ''}
              onChange={(event) => setProfile({ ...profile, position: event.target.value })}
            />
          </label>
          <label>
            Plant name
            <input
              value={profile.plant_name ?? ''}
              onChange={(event) => setProfile({ ...profile, plant_name: event.target.value })}
            />
          </label>
        </>
      ) : (
        <>
          <label>
            Current institution
            <input
              value={profile.current_institution ?? ''}
              onChange={(event) => setProfile({ ...profile, current_institution: event.target.value })}
            />
          </label>
          <label>
            Field of study
            <input
              value={profile.field_of_study ?? ''}
              onChange={(event) => setProfile({ ...profile, field_of_study: event.target.value })}
            />
          </label>
        </>
      )}

      <label>
        LinkedIn URL
        <input
          value={profile.linkedin_url ?? ''}
          onChange={(event) => setProfile({ ...profile, linkedin_url: event.target.value })}
        />
      </label>

      <label>
        Job availability
        <select
          value={profile.job_availability ?? ''}
          onChange={(event) => setProfile({ ...profile, job_availability: event.target.value as ProfileForm['job_availability'] })}
        >
          <option value="">Not set</option>
          <option value="open">Open</option>
          <option value="not_looking">Not looking</option>
          <option value="open_to_opportunities">Open to opportunities</option>
        </select>
      </label>

      <label>
        <input
          type="checkbox"
          checked={profile.is_discoverable}
          onChange={(event) => setProfile({ ...profile, is_discoverable: event.target.checked })}
        />
        Discoverable in Expert Directory
      </label>

      <label>
        Contact visibility
        <select
          value={profile.privacy_settings.contact_visibility ?? 'connections_only'}
          onChange={(event) => setProfile({
            ...profile,
            privacy_settings: {
              ...profile.privacy_settings,
              contact_visibility: event.target.value as ProfileForm['privacy_settings']['contact_visibility'],
            },
          })}
        >
          <option value="public">Public</option>
          <option value="connections_only">Connections only</option>
          <option value="private">Private</option>
        </select>
      </label>

      {error?.errors
        ? Object.entries(error.errors).map(([field, messages]) => (
            <p key={field} role="alert">{field}: {messages.join(', ')}</p>
          ))
        : null}

      <button type="submit" disabled={saving}>
        {saving ? 'Saving...' : 'Save profile'}
      </button>
    </form>
  );
}
