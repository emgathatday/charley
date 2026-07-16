@extends('layouts.master')

@section('title', 'QA Weekly Themes')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">QA Weekly Themes</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li><li class="breadcrumb-item active" aria-current="page">Weekly Themes</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')
            @include('admin.qa.components.action-tabs')

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Create Weekly Theme</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.store') }}" class="row g-3">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label" for="title">Title</label>
                                    <input id="title" name="title" class="form-control" value="Rotating equipment reliability" placeholder="Theme title">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Editorial campaign goal and guidance">Demo editorial campaign for pumps, compressors, turbines, and vibration troubleshooting.</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="week_start_date">Start date</label>
                                    <input id="week_start_date" name="week_start_date" type="date" class="form-control" value="{{ now()->startOfWeek()->toDateString() }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="week_end_date">End date</label>
                                    <input id="week_end_date" name="week_end_date" type="date" class="form-control" value="{{ now()->endOfWeek()->toDateString() }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="status">Status</label>
                                    <select id="status" name="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Weekly Theme</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-outline card-info mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Assign Question To Theme</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.assign-question', $themes->first()->id ?? 9301) }}" class="row g-3 align-items-end">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label" for="assign_theme_id">Weekly theme</label>
                                    <select id="assign_theme_id" class="form-select" onchange="this.form.action=this.options[this.selectedIndex].dataset.action">
                                        @foreach ($themes as $theme)
                                            <option value="{{ $theme->id }}" data-action="{{ route('admin.dashboard.qa.weekly-themes.assign-question', $theme->id) }}">{{ $theme->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="question_id">Question</label>
                                    <select id="question_id" name="question_id" class="form-select">
                                        @foreach ($assignableQuestions as $question)
                                            <option value="{{ $question['id'] }}">#{{ $question['id'] }} · {{ $question['title'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-link-45deg me-1"></i>Assign Demo Question</button></div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title mb-0">Theme Calendar And Assignments</h3></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>Theme</th><th>Dates</th><th>Status</th><th>Questions</th><th class="text-end">Controls</th></tr></thead>
                                    <tbody>
                                    @forelse ($themes as $theme)
                                        @php($assignedQuestions = $themeAssignments->get($theme->id, collect()))
                                        <tr>
                                            <td><div class="fw-semibold">{{ $theme->title }}</div><div class="small text-body-secondary">{{ $theme->description }}</div></td>
                                            <td>{{ $theme->week_start_date }} to {{ $theme->week_end_date }}</td>
                                            <td><span class="badge text-bg-{{ $theme->status === 'active' ? 'success' : 'secondary' }}">{{ Str::headline($theme->status) }}</span></td>
                                            <td><span class="badge text-bg-info">{{ $assignedQuestions->count() ?: ($theme->assigned_questions_count ?? 0) }} assigned</span></td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.status', [$theme->id, $theme->status === 'active' ? 'archived' : 'active']) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-primary" type="submit">{{ $theme->status === 'active' ? 'Archive' : 'Activate' }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="bg-body-tertiary">
                                                <div class="d-flex flex-column gap-2">
                                                    @forelse ($assignedQuestions as $question)
                                                        <div class="d-flex justify-content-between align-items-center border rounded p-2 bg-body">
                                                            <div><span class="fw-semibold">#{{ $question['id'] }}</span> {{ $question['title'] }} <span class="badge text-bg-light ms-2">{{ $question['plant'] }}</span></div>
                                                            <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.remove-question', [$theme->id, $question['id']]) }}">
                                                                @csrf
                                                                <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-x-lg me-1"></i>Remove</button>
                                                            </form>
                                                        </div>
                                                    @empty
                                                        <div class="text-body-secondary small">No questions assigned to this weekly theme yet.</div>
                                                    @endforelse
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted text-center py-3" colspan="5">No weekly themes available.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection