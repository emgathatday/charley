import type { Metadata } from 'next';
import { ExpertDirectoryWorkspace } from '../../../components/profiles/ExpertDirectoryWorkspace';

export const metadata: Metadata = {
  title: 'Expert Directory',
  description: 'Search discoverable Professional and Unverified Member profiles.',
};

export default function ExpertDirectoryPage() {
  return <ExpertDirectoryWorkspace />;
}
