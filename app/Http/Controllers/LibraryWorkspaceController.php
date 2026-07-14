<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LibraryWorkspaceController extends Controller
{
    public function index(): View
    {
        return view('library.index', [
            'items' => $this->items(),
            'categories' => ['Technical Manuals', 'Case Studies', 'Safety Bulletins', 'Presentations'],
            'plantTypes' => ['Combined Cycle', 'Transmission', 'Solar Utility', 'Hydro'],
            'domains' => $this->domains(),
            'selectedItem' => $this->items()[0],
            'selectedDomain' => $this->domains()[0],
            'attemptHistory' => $this->attemptHistory(),
            'uiStates' => [
                'loading' => 'Loading approved library content...',
                'empty' => 'No approved documents match the selected filters.',
                'error' => 'Library content could not be loaded. Retry or adjust filters.',
                'cooldown' => 'Next quiz attempt opens in 18h 24m.',
            ],
        ]);
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
                'domain' => 'Gas Turbine Operations',
                'content_type' => 'document',
                'access_level' => 'professional_only',
                'summary' => 'Approved step-by-step maintenance checklist with inspection gates, safe drains, and operator hold points.',
                'approved_at' => '2026-07-11',
                'file_media_id' => 'MF-8842',
                'file_label' => 'gt-compressor-wash-v3.pdf',
                'download_allowed' => true,
                'copy_paste_disabled' => true,
                'view_count' => 1240,
                'download_count' => 184,
                'ai_trainable' => true,
            ],
            [
                'id' => 102,
                'title' => 'Transformer Oil Analysis Case Study',
                'slug' => 'transformer-oil-analysis-case-study',
                'category' => 'Case Studies',
                'plant_type' => 'Transmission',
                'domain' => 'Transformer Diagnostics',
                'content_type' => 'case_study',
                'access_level' => 'partner_only',
                'summary' => 'Dissolved gas analysis trend review for transformer intervention planning and maintenance prioritization.',
                'approved_at' => '2026-07-09',
                'file_media_id' => 'MF-8721',
                'file_label' => 'transformer-oil-dga-case-study.pptx',
                'download_allowed' => false,
                'copy_paste_disabled' => true,
                'view_count' => 318,
                'download_count' => 0,
                'ai_trainable' => false,
            ],
            [
                'id' => 103,
                'title' => 'Arc Flash Safety Refresher',
                'slug' => 'arc-flash-safety-refresher',
                'category' => 'Safety Bulletins',
                'plant_type' => 'Solar Utility',
                'domain' => 'Arc Flash Safety',
                'content_type' => 'video',
                'access_level' => 'gold',
                'summary' => 'Short safety refresher for switching teams with restricted download policy and searchable transcript.',
                'approved_at' => '2026-07-07',
                'file_media_id' => 'MF-8898',
                'file_label' => 'arc-flash-refresher.mp4',
                'download_allowed' => false,
                'copy_paste_disabled' => false,
                'view_count' => 876,
                'download_count' => 12,
                'ai_trainable' => true,
            ],
        ];
    }

    private function domains(): array
    {
        return [
            [
                'id' => 201,
                'name' => 'Gas Turbine Operations',
                'plant_type' => 'Combined Cycle',
                'question_count' => 86,
                'passed_count' => 7,
                'last_score' => 76,
                'cooldown_until' => '2026-07-14 08:40',
                'questions' => [
                    [
                        'text' => 'Which parameter is the primary trigger to abort a compressor wash during crank operation?',
                        'difficulty' => 'medium',
                        'choices' => [
                            ['text' => 'Bearing vibration above procedure limit', 'correct' => true],
                            ['text' => 'Ambient temperature below 30 C', 'correct' => false],
                            ['text' => 'Operator shift handover starts', 'correct' => false],
                        ],
                        'explanation' => 'High vibration indicates unsafe wash conditions and requires aborting the procedure.',
                    ],
                    [
                        'text' => 'What should be verified before declaring a failed start cooldown complete?',
                        'difficulty' => 'hard',
                        'choices' => [
                            ['text' => 'Purge cycle and rotor thermal limits are satisfied', 'correct' => true],
                            ['text' => 'Only the start counter is reset', 'correct' => false],
                            ['text' => 'Fuel gas pressure is at maximum', 'correct' => false],
                        ],
                        'explanation' => 'Cooldown verification requires rotor temperature, purge, and OEM timing checks.',
                    ],
                ],
            ],
            [
                'id' => 202,
                'name' => 'Transformer Diagnostics',
                'plant_type' => 'Transmission',
                'question_count' => 64,
                'passed_count' => 3,
                'last_score' => 92,
                'cooldown_until' => null,
                'questions' => [],
            ],
        ];
    }

    private function attemptHistory(): array
    {
        return [
            ['domain' => 'Transformer Diagnostics', 'score' => 92, 'result' => 'Passed', 'submitted_at' => '2026-07-10 14:22', 'next_attempt' => null],
            ['domain' => 'Gas Turbine Operations', 'score' => 76, 'result' => 'Cooldown', 'submitted_at' => '2026-07-13 08:40', 'next_attempt' => '2026-07-14 08:40'],
            ['domain' => 'Arc Flash Safety', 'score' => 84, 'result' => 'Passed', 'submitted_at' => '2026-07-08 10:12', 'next_attempt' => null],
        ];
    }
}