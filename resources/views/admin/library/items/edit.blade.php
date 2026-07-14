@extends('layouts.master')

@section('title', 'Edit Library Item')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Edit Library Item</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.items.index') }}">Library Items</a></li><li class="breadcrumb-item active" aria-current="page">Edit</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <form method="POST" action="{{ route('admin.dashboard.library.items.index') }}">
            @csrf
            @include('admin.library.items._form')
            <div class="d-flex justify-content-between gap-2 mb-3"><a href="{{ route('admin.dashboard.library.items.show', $item['id']) }}" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i>Preview</a><div class="d-flex gap-2"><a href="{{ route('admin.dashboard.library.items.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="button" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Approve</button><button type="button" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button></div></div>
        </form>
    </div></div>
@endsection