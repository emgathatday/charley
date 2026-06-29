<?php

namespace Tests\Unit;

use App\Models\EngineerProfile;
use App\Models\User;
use App\Services\ProfileSearchIndexService;
use InvalidArgumentException;
use ReflectionMethod;
use Tests\TestCase;

class ProfileSearchIndexServiceTest extends TestCase
{
    private ProfileSearchIndexService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProfileSearchIndexService();
    }

    public function test_refresh_rejects_non_profile_models(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only profile models can be indexed.');

        $this->service->refresh(new User());
    }

    public function test_expert_directory_query_filters_discoverable_entries_and_search_term(): void
    {
        $query = $this->service->expertDirectoryQuery('pump');

        $this->assertStringContainsString('where "search_context" = ?', $query->toSql(), 'Expert directory query should constrain search context.');
        $this->assertStringContainsString('"is_discoverable" = ?', $query->toSql(), 'Expert directory query should constrain discoverability.');
        $this->assertStringContainsString('"searchable_text" like ?', $query->toSql(), 'Expert directory query should include term search.');
        $this->assertSame(['expert_directory', true, '%pump%'], $query->getBindings(), 'Expert directory query should bind context, discoverability, and term.');
    }

    public function test_searchable_text_normalizes_profile_fields_and_skips_null_values(): void
    {
        $profile = new EngineerProfile([
            'current_company' => 'Acme Plant',
            'position' => 'Reliability Engineer',
            'bio' => null,
            'expertise_tags' => ['maintenance', 'automation'],
            'searchable_keywords' => [],
        ]);

        $text = $this->invokePrivate('searchableText', $profile);

        $this->assertSame('Acme Plant Reliability Engineer maintenance automation', $text, 'Search text should join populated profile fields.');
    }

    public function test_structured_data_keeps_directory_fields(): void
    {
        $profile = new EngineerProfile([
            'user_id' => 7,
            'experience_years' => 12,
            'expertise_tags' => ['safety'],
            'searchable_keywords' => ['audit'],
            'job_availability' => 'open',
            'is_discoverable' => true,
        ]);

        $data = $this->invokePrivate('structuredData', $profile);

        $this->assertSame(EngineerProfile::class, $data['profile_type'], 'Structured data should include profile type.');
        $this->assertSame(7, $data['user_id'], 'Structured data should include owner.');
        $this->assertSame(12, $data['experience_years'], 'Structured data should include experience.');
        $this->assertSame(['safety'], $data['expertise_tags'], 'Structured data should include tags.');
        $this->assertSame(['audit'], $data['searchable_keywords'], 'Structured data should include keywords.');
        $this->assertSame('open', $data['job_availability'], 'Structured data should include availability.');
        $this->assertTrue($data['is_discoverable'], 'Structured data should include discoverability.');
    }

    /**
     * @return mixed
     */
    private function invokePrivate(string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod(ProfileSearchIndexService::class, $method);

        return $reflection->invoke($this->service, ...$arguments);
    }
}
