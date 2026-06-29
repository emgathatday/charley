# Profiles Frontend Integration

## Scope

This document defines the first static frontend plan for the Profiles module. No concrete Next.js routes are implemented in this task.

## Routes

| Route | Access | Rendering | Purpose |
|---|---|---|---|
| `/profile/engineer` | Professional | Client page | Edit the authenticated Professional profile. |
| `/profile/unverified` | Unverified Member | Client page | Edit the authenticated Unverified Member profile. |
| `/profiles/engineers/[id]` | Authenticated members | Server page with client actions | Show a discoverable Professional profile. |
| `/profiles/unverified-members/[id]` | Professional, Partner | Server page with client actions | Show a discoverable Unverified Member profile. |
| `/expert-directory` | Authenticated members | Client page | Search and filter discoverable expert profiles. |
| `/connections` | Professional, Partner | Client page | Manage connection requests and network state. |
| `/settings/privacy` | Professional, Unverified Member | Client page | Manage profile privacy and notification settings. |

## Page States

### Profile Edit

- Empty profile state with required identity fields prefilled from the authenticated user.
- Draft editing state for bio, company or institution, expertise tags, job availability, LinkedIn URL, privacy settings and notification preferences.
- Media upload state for profile photo and Professional verification document, using centralized `media_files` references.
- Save success state with updated timestamps and visible discoverability status.
- Validation error state for malformed URLs, invalid enum values and unsupported media references.
- Unauthorized state for role mismatch, inactive user status or unverified Professional constraints.

### Public Profile View

- Discoverable Professional profile state with avatar, headline, company, plant name, expertise tags, availability and reputation summary.
- Discoverable Unverified Member profile state with avatar, institution, field of study, expertise tags and verification intent.
- Private or hidden state when `is_discoverable` is false.
- Contact visibility state based on `privacy_settings`, including public, connections-only and hidden values.
- Connection action state for connect, pending, accepted, declined and blocked relationships.

### Expert Directory

- Initial empty search state with filters for expertise tags, job availability, profile type and search context.
- Loading state while querying `/api/v1/expert-directory`.
- Result list state grouped by Professional and Unverified Member entries.
- Empty result state when no discoverable profile matches.
- Pagination or incremental loading state when API pagination is added.
- Error state for authorization failure or API unavailability.

### Connections

- Incoming pending requests.
- Outgoing pending requests.
- Accepted connections.
- Declined or blocked history where available.
- Action states for accept, decline and block.
- Disabled actions for inactive users and blocked connection pairs.

### Privacy Settings

- Discoverability toggle for Expert Directory presence.
- Field-level visibility controls for email, phone, LinkedIn, activity feed and contact actions.
- Notification preference controls for connection requests, profile visibility and verification reminders.
- Save, saving, saved and validation states.

## Components

| Component | Responsibility |
|---|---|
| `ProfileEditor` | Receives profile data, editable field options and submit handler. |
| `ProfilePhotoUploader` | Integrates profile photo media selection and upload result display. |
| `VerificationDocumentUploader` | Handles Professional verification document media reference. |
| `PublicProfileHeader` | Displays avatar, headline, role, discoverability and action summary. |
| `PrivacyControls` | Renders privacy and notification settings from structured props. |
| `ExpertDirectoryFilters` | Controls search text, expertise, availability and context filters. |
| `ExpertDirectoryResults` | Renders normalized search index result cards. |
| `ConnectionActions` | Renders connect, accept, decline and block controls based on state. |
| `ConnectionList` | Displays grouped connection records. |

Interactive components must be client components. Display-only components should remain server components by default.

## API Contracts

| UI Action | API |
|---|---|
| Load my Professional profile | `GET /api/v1/profile/engineer` |
| Save my Professional profile | `PUT /api/v1/profile/engineer` |
| Load my Unverified Member profile | `GET /api/v1/profile/unverified` |
| Save my Unverified Member profile | `PUT /api/v1/profile/unverified` |
| View Professional profile | `GET /api/v1/profiles/engineers/{engineerProfile}` |
| View Unverified Member profile | `GET /api/v1/profiles/unverified-members/{unverifiedMemberProfile}` |
| List connections | `GET /api/v1/connections` |
| Create connection | `POST /api/v1/connections` |
| Accept connection | `POST /api/v1/connections/{connection}/accept` |
| Decline connection | `POST /api/v1/connections/{connection}/decline` |
| Block connection | `POST /api/v1/connections/{connection}/block` |
| Search Expert Directory | `GET /api/v1/expert-directory` |
| Upload media | `POST /api/v1/media-files` |
| Attach media | `POST /api/v1/media-files/{mediaFile}/attach` |

## Authorization Expectations

- Professional profile editing is limited to active, verified Professional users.
- Unverified Member profile editing is limited to active Unverified Member users.
- Admin review paths are admin-only in backend policy, while owner-facing review status is read-only in the frontend.
- Public profile pages must respect `is_discoverable` and field privacy settings.
- Connection actions are limited to active Professional and Partner users.
- Expert Directory results must only display discoverable `search_index_entries` allowed by policy.

## Static Demo Data

Frontend mock data should include:

- One verified Professional with a complete discoverable profile and public contact settings.
- One verified Professional with connections-only contact settings.
- One Unverified Member with `verification_intent=true`.
- One hidden profile with `is_discoverable=false`.
- One pending incoming connection.
- One pending outgoing connection.
- One accepted connection.
- One blocked connection state.
- Empty Expert Directory search results.
- API validation error payloads for invalid URL, invalid enum and unauthorized profile update.

## Later Implementation Notes

- Use App Router pages and route-level `loading.tsx` and `error.tsx` where data is fetched.
- Public profile pages should export metadata for SEO when exposed outside authenticated areas.
- Authenticated edit, connection and directory pages should use client-side state for filters, form drafts and actions.
- Components must receive typed props or hook data and should not call APIs directly unless wrapped in a route-level data hook.
