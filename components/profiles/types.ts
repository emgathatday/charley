export type ApiError = {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
};

export type PrivacySettings = {
  show_email?: 'public' | 'connections_only' | 'none';
  show_phone?: 'public' | 'connections_only' | 'none';
  show_activity_feed?: boolean;
  contact_visibility?: 'public' | 'connections_only' | 'private';
};

export type ProfileForm = {
  bio: string;
  current_company?: string;
  current_institution?: string;
  position?: string;
  plant_name?: string;
  field_of_study?: string;
  experience_years?: number | '';
  linkedin_url?: string;
  job_availability?: 'open' | 'not_looking' | 'open_to_opportunities' | '';
  expertise_tags: string[];
  searchable_keywords: string[];
  is_discoverable: boolean;
  privacy_settings: PrivacySettings;
  notification_preferences: Record<string, boolean>;
  verification_intent?: boolean;
};

export type ProfileKind = 'engineer' | 'unverified';

export type PublicProfile = ProfileForm & {
  id: number;
  user_id: number;
  display_name?: string;
  role_label?: string;
  reputation_points?: number;
  connection_status?: 'none' | 'pending' | 'accepted' | 'declined' | 'blocked';
};

export type ExpertDirectoryResult = {
  id: number;
  indexable_type: string;
  indexable_id: number;
  searchable_text: string;
  structured_data: {
    display_name?: string;
    role_label?: string;
    expertise_tags?: string[];
    job_availability?: string;
    company?: string;
    institution?: string;
  };
  search_context: 'expert_directory' | 'partner_directory' | 'global';
  is_discoverable: boolean;
};

export type ConnectionRecord = {
  id: number;
  requester_id: number;
  receiver_id: number;
  status: 'pending' | 'accepted' | 'declined' | 'blocked';
  initiated_context: 'engineer_to_engineer' | 'partner_to_engineer' | 'engineer_to_partner';
  requester?: { id: number; name?: string; role?: string };
  receiver?: { id: number; name?: string; role?: string };
};
