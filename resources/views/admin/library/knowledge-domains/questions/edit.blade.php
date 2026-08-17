@extends('layouts.rebuild-dashboard')

@section('title', 'Edit Quiz Question')

@section('content')
    @include('templates.components.alert-session')

    <a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}#questions" class="back-link">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
        Back to Domain Questions
    </a>

    @include('admin.library.knowledge-domains.questions._form')
@endsection
