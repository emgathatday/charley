<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LibraryItemPageController extends Controller
{
    public function index(): View
    {
        return view('admin.library.items', [
            'items' => $this->items(),
            'stats' => [
                'total' => 42,
                'published' => 28,
                'pending_review' => 9,
                'ai_trainable' => 31,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.library.items.create', [
            'item' => $this->blankItem(),
            'categories' => $this->categories(),
            'plantTypes' => $this->plantTypes(),
            'contentTypes' => $this->contentTypes(),
            'accessLevels' => $this->accessLevels(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(int $libraryItem): View
    {
        return view('admin.library.items.show', [
            'item' => $this->findItem($libraryItem),
            'recentAccessLogs' => $this->recentAccessLogs(),
        ]);
    }

    public function edit(int $libraryItem): View
    {
        return view('admin.library.items.edit', [
            'item' => $this->findItem($libraryItem),
            'categories' => $this->categories(),
            'plantTypes' => $this->plantTypes(),
            'contentTypes' => $this->contentTypes(),
            'accessLevels' => $this->accessLevels(),
            'statuses' => $this->statuses(),
        ]);
    }

    private function findItem(int $id): array
    {
        foreach ($this->items() as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return $this->items()[0];
    }

    private function blankItem(): array
    {
        return [
            'id' => null,
            'title' => '',
            'slug' => '',
            'category' => 'Technical Manuals',
            'plant_type' => 'Combined Cycle',
            'content_type' => 'document',
            'item_type' => 'handbook',
            'access_level' => 'professional_only',
            'status' => 'draft',
            'summary' => '',
            'content' => '',
            'author' => '',
            'source' => '',
            'year' => now()->year,
            'published_year' => now()->year,
            'file_media_id' => '',
            'file_label' => 'No media selected',
            'download_allowed' => false,
            'copy_paste_disabled' => true,
            'is_ai_trainable' => true,
            'download_count' => 0,
            'view_count' => 0,
            'approval_status' => 'Needs review',
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    private function items(): array
    {
        return [
            [
                'id' => 101,
                'title' => 'Gas Turbine Compressor Wash Procedure',
                'slug' => 'gas-turbine-compressor-wash-procedure',
                'category' => 'Technical Manuals',
                'plant_type' => 'Combined Cycle',
                'content_type' => 'document',
                'item_type' => 'handbook',
                'access_level' => 'professional_only',
                'status' => 'published',
                'summary' => 'Step-by-step maintenance checklist with safety gates and inspection criteria.',
                'content' => 'Operational procedure content prepared for AI-assisted retrieval after approval.',
                'author' => 'Operations Excellence Team',
                'source' => 'OEM field bulletin GT-204',
                'year' => 2026,
                'published_year' => 2026,
                'file_media_id' => 'MF-8842',
                'file_label' => 'gt-compressor-wash-v3.pdf',
                'download_allowed' => true,
                'copy_paste_disabled' => true,
                'is_ai_trainable' => true,
                'download_count' => 184,
                'view_count' => 1240,
                'approval_status' => 'Approved',
                'approved_by' => 'Ari Nguyen',
                'approved_at' => '2026-07-11 09:24',
            ],
            [
                'id' => 102,
                'title' => 'Transformer Oil Analysis Case Study',
                'slug' => 'transformer-oil-analysis-case-study',
                'category' => 'Case Studies',
                'plant_type' => 'Transmission',
                'content_type' => 'case_study',
                'item_type' => 'article',
                'access_level' => 'partner_only',
                'status' => 'draft',
                'summary' => 'Visual review of dissolved gas analysis trends before transformer intervention.',
                'content' => 'Draft case study body waiting for technical approval and source verification.',
                'author' => 'Asset Reliability Group',
                'source' => 'Internal RCA archive',
                'year' => 2025,
                'published_year' => 2025,
                'file_media_id' => 'MF-8721',
                'file_label' => 'transformer-oil-dga-case-study.pptx',
                'download_allowed' => false,
                'copy_paste_disabled' => true,
                'is_ai_trainable' => false,
                'download_count' => 0,
                'view_count' => 318,
                'approval_status' => 'Needs review',
                'approved_by' => null,
                'approved_at' => null,
            ],
            [
                'id' => 103,
                'title' => 'Arc Flash Safety Refresher',
                'slug' => 'arc-flash-safety-refresher',
                'category' => 'Safety Bulletins',
                'plant_type' => 'Solar Utility',
                'content_type' => 'video',
                'item_type' => 'video',
                'access_level' => 'gold_plus',
                'status' => 'published',
                'summary' => 'Short safety refresher for switching teams with restricted download policy.',
                'content' => 'Video transcript and chapter markers are available for search after approval.',
                'author' => 'HSE Academy',
                'source' => 'Safety training library',
                'year' => 2026,
                'published_year' => 2026,
                'file_media_id' => 'MF-8898',
                'file_label' => 'arc-flash-refresher.mp4',
                'download_allowed' => false,
                'copy_paste_disabled' => false,
                'is_ai_trainable' => true,
                'download_count' => 12,
                'view_count' => 876,
                'approval_status' => 'Approved',
                'approved_by' => 'Minh Tran',
                'approved_at' => '2026-07-09 15:10',
            ],
        ];
    }

    private function recentAccessLogs(): array
    {
        return [
            ['user' => 'quang.partner@example.com', 'action' => 'view', 'ip_address' => '10.42.5.12', 'created_at' => '2026-07-13 10:25'],
            ['user' => 'linh.ops@example.com', 'action' => 'download', 'ip_address' => '10.42.8.44', 'created_at' => '2026-07-13 09:41'],
            ['user' => 'maya.hse@example.com', 'action' => 'view', 'ip_address' => '10.42.2.91', 'created_at' => '2026-07-12 17:18'],
        ];
    }

    private function categories(): array
    {
        return ['Technical Manuals', 'Case Studies', 'Safety Bulletins', 'Presentations'];
    }

    private function plantTypes(): array
    {
        return ['Combined Cycle', 'Transmission', 'Solar Utility', 'Hydro'];
    }

    private function contentTypes(): array
    {
        return ['article', 'video', 'document', 'presentation', 'case_study', 'safety_bulletin'];
    }

    private function accessLevels(): array
    {
        return ['public', 'partner_only', 'professional_only', 'gold_plus'];
    }

    private function statuses(): array
    {
        return ['draft', 'published', 'archived'];
    }
}