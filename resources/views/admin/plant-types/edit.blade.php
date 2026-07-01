@extends('layouts.master')

@section('title', 'Edit Plant Type')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">Edit Plant Type</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.plant-types.index') }}">Plant Types</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $plantType->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <p class="text-body-secondary mb-0">Update shared plant category #{{ $plantType->id }}.</p>
                <a href="{{ route('admin.dashboard.plant-types.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>
                    Back
                </a>
            </div>

            <div class="card card-outline card-primary">
                <form method="POST" action="{{ route('admin.dashboard.plant-types.update', $plantType) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $plantType->name }}</h3>
                    </div>
                    @include('admin.plant-types._form', ['plantType' => $plantType])
                    <div class="card-footer d-flex flex-column flex-md-row justify-content-between gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2 me-1"></i>
                            Save Changes
                        </button>
                        <button type="submit" form="delete-plant-type-{{ $plantType->id }}" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>
                            Delete
                        </button>
                    </div>
                </form>
            </div>

            <form id="delete-plant-type-{{ $plantType->id }}" method="POST" action="{{ route('admin.dashboard.plant-types.destroy', $plantType) }}" onsubmit="return confirm('Delete this plant type?');">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
@endsection
