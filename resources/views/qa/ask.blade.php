@extends('qa.layout')

@section('title', 'Ask Question')

@section('header')
    <h1 class="m-0">Ask Question</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ url('/api/v1/qa/questions') }}" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input id="title" name="title" type="text" class="form-control" placeholder="Summarize the technical question">
                        </div>
                        <div class="form-group">
                            <label for="plant_type_id">Plant type</label>
                            <select id="plant_type_id" name="plant_type_id" class="form-control">
                                <option value="">Select plant type</option>
                                @foreach ($plantTypes as $plantType)
                                    <option value="{{ $plantType->id }}">{{ $plantType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="weekly_theme_id">Weekly theme</label>
                            <select id="weekly_theme_id" name="weekly_theme_id" class="form-control">
                                <option value="">No theme</option>
                                @foreach ($weeklyThemes as $weeklyTheme)
                                    <option value="{{ $weeklyTheme->id }}">{{ $weeklyTheme->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="body">Details</label>
                            <textarea id="body" name="body" class="form-control" rows="7" placeholder="Add operating context, observations, and constraints"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="media">Attachments</label>
                            <input id="media" name="attachment_media_ids[]" type="file" class="form-control" multiple>
                        </div>
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="anonymous" name="is_anonymous" value="1">
                            <label class="custom-control-label" for="anonymous">Post anonymously</label>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-send mr-1"></i> Submit Question</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @include('qa.components.leaderboard', ['leaders' => $leaders ?? []])
        </div>
    </div>
@endsection