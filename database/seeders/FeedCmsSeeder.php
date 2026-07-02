<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeedCmsSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');

        $priorityModel = new HomepageFeedPrioritySeedModel();
        foreach ($this->feedPriorities($adminUserId) as $priority) {
            $priorityModel->newQuery()->firstOrCreate(
                ['content_type' => $priority['content_type']],
                $priority,
            );
        }

        $pageModel = new PageSeedModel();
        $revisionModel = new PageRevisionSeedModel();

        foreach ($this->systemPages($adminUserId) as $page) {
            $cmsPage = $pageModel->newQuery()->firstOrCreate(
                ['slug' => $page['slug']],
                $page,
            );

            if ($adminUserId && ! $revisionModel->newQuery()->where('page_id', $cmsPage->id)->exists()) {
                $revisionModel->newQuery()->firstOrCreate(
                    [
                        'page_id' => $cmsPage->id,
                        'changed_by' => $adminUserId,
                    ],
                    [
                        'content_blocks' => $cmsPage->content_blocks,
                        'change_summary' => 'Initial system page content.',
                        'created_at' => now(),
                    ],
                );
            }
        }

        $sampleUserId = DB::table('users')->orderBy('id')->value('id');
        $samplePageId = DB::table('pages')->where('status', 'published')->orderBy('id')->value('id');

        if ($sampleUserId && $samplePageId) {
            $feedCacheModel = new UserFeedCacheSeedModel();

            $feedCacheModel->newQuery()->firstOrCreate(
                [
                    'user_id' => $sampleUserId,
                    'feedable_type' => 'App\\Models\\Page',
                    'feedable_id' => $samplePageId,
                ],
                [
                    'priority_score' => 75,
                    'source_reason' => 'fresh_content',
                    'is_seen' => false,
                    'created_at' => now(),
                    'expires_at' => now()->addDays(7),
                ],
            );
        }
    }

    private function feedPriorities(?int $adminUserId): array
    {
        return [
            ['content_type' => 'partner_announcement', 'priority_weight' => 90, 'is_highlighted' => true, 'highlight_color' => '#0d6efd', 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'network_post', 'priority_weight' => 60, 'is_highlighted' => false, 'highlight_color' => null, 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'unanswered_question', 'priority_weight' => 80, 'is_highlighted' => true, 'highlight_color' => '#ffc107', 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'library_item', 'priority_weight' => 55, 'is_highlighted' => false, 'highlight_color' => null, 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'handbook_article', 'priority_weight' => 50, 'is_highlighted' => false, 'highlight_color' => null, 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'event', 'priority_weight' => 70, 'is_highlighted' => true, 'highlight_color' => '#198754', 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'job', 'priority_weight' => 45, 'is_highlighted' => false, 'highlight_color' => null, 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'poll', 'priority_weight' => 40, 'is_highlighted' => false, 'highlight_color' => null, 'is_active' => true, 'updated_by' => $adminUserId],
            ['content_type' => 'service', 'priority_weight' => 35, 'is_highlighted' => false, 'highlight_color' => null, 'is_active' => true, 'updated_by' => $adminUserId],
        ];
    }

    private function systemPages(?int $adminUserId): array
    {
        return [
            [
                'title' => 'About Charley',
                'slug' => 'about',
                'content_blocks' => [
                    ['type' => 'heading', 'content' => 'About Charley'],
                    ['type' => 'paragraph', 'content' => 'Charley connects technical professionals, partner organizations, and platform knowledge workflows.'],
                ],
                'status' => 'published',
                'is_system_page' => true,
                'view_count' => 0,
                'seo_meta' => ['title' => 'About Charley', 'description' => 'Overview of the Charley technical platform.'],
                'user_id' => $adminUserId,
                'published_at' => now(),
            ],
            [
                'title' => 'Terms of Use',
                'slug' => 'terms',
                'content_blocks' => [
                    ['type' => 'heading', 'content' => 'Terms of Use'],
                    ['type' => 'paragraph', 'content' => 'Use this local demo page as a safe placeholder for platform terms content.'],
                ],
                'status' => 'published',
                'is_system_page' => true,
                'view_count' => 0,
                'seo_meta' => ['title' => 'Terms of Use', 'description' => 'Platform terms placeholder.'],
                'user_id' => $adminUserId,
                'published_at' => now(),
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content_blocks' => [
                    ['type' => 'heading', 'content' => 'Privacy Policy'],
                    ['type' => 'paragraph', 'content' => 'Use this local demo page as a safe placeholder for privacy policy content.'],
                ],
                'status' => 'draft',
                'is_system_page' => true,
                'view_count' => 0,
                'seo_meta' => ['title' => 'Privacy Policy', 'description' => 'Privacy policy placeholder.'],
                'user_id' => $adminUserId,
                'published_at' => null,
            ],
        ];
    }
}

class HomepageFeedPrioritySeedModel extends Model
{
    protected $table = 'homepage_feed_priorities';

    protected $guarded = [];
}

class PageSeedModel extends Model
{
    protected $table = 'pages';

    protected $guarded = [];

    protected $casts = [
        'content_blocks' => 'array',
        'seo_meta' => 'array',
        'published_at' => 'datetime',
    ];
}

class PageRevisionSeedModel extends Model
{
    public $timestamps = false;

    protected $table = 'page_revisions';

    protected $guarded = [];

    protected $casts = [
        'content_blocks' => 'array',
        'created_at' => 'datetime',
    ];
}

class UserFeedCacheSeedModel extends Model
{
    public $timestamps = false;

    protected $table = 'user_feed_cache';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
