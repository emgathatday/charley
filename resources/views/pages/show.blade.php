@extends('layouts.master')

@section('title', $page->title)

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8"><h1 class="mb-0">{{ $page->title }}</h1></div>
                <div class="col-sm-4"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('pages.index') }}">Pages</a></li><li class="breadcrumb-item active" aria-current="page">{{ $page->slug }}</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-4"><span class="badge text-bg-success">Published</span><span class="text-body-secondary">{{ $page->view_count }} views</span></div>
                <article class="fs-5 lh-lg">
                    @foreach (($page->content_blocks ?? []) as $block)
                        @php($type = is_array($block) ? ($block['type'] ?? 'paragraph') : 'paragraph')
                        @php($content = is_array($block) ? ($block['content'] ?? '') : $block)
                        @if ($type === 'heading')
                            <h2 class="h3 mt-4 mb-3">{{ $content }}</h2>
                        @elseif ($type === 'quote')
                            <blockquote class="blockquote border-start border-4 ps-3 text-body-secondary">{{ $content }}</blockquote>
                        @else
                            <p>{{ $content }}</p>
                        @endif
                    @endforeach
                </article>
            </div>
            <div class="card-footer d-flex justify-content-between"><a href="{{ route('pages.index') }}" class="btn btn-outline-secondary">All Pages</a><a href="{{ route('feed.index') }}" class="btn btn-primary">Open Feed</a></div>
        </div>
    </div></div>
@endsection
