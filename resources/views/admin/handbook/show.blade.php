@extends('layouts.master')

@section('title', 'Handbook Article Detail')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1>Start-up Readiness Walkthrough</h1>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Admin</a></li>
                        <li class="breadcrumb-item"><a href="#">Handbook</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Published Article</h3>
                            <div class="card-tools">
                                <span class="badge badge-success">Published</span>
                                <span class="badge badge-info">AI Trainable</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Category</dt>
                                <dd class="col-sm-9">Plant Overview</dd>
                                <dt class="col-sm-3">Summary</dt>
                                <dd class="col-sm-9">Demo handbook article for start-up readiness checks and operating handover.</dd>
                                <dt class="col-sm-3">Process</dt>
                                <dd class="col-sm-9">A controlled readiness sequence covering personnel, process safety, equipment status, and operating limits.</dd>
                            </dl>
                            <hr>
                            <h5>Content</h5>
                            <p>Use this demo handbook entry to validate navigation, metadata, and AI-training flows before approved operating procedures are published.</p>
                            <h5>Optimization Guidance</h5>
                            <p>Review interlocks, permits, critical alarms, and shift handover notes before releasing the unit to operation.</p>
                        </div>
                    </div>

                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Failure Modes</h3>
                        </div>
                        <div class="card-body">
                            <div class="callout callout-danger">
                                <h5>Incomplete permit review</h5>
                                <p>Confirm permit sign-off before equipment energization.</p>
                            </div>
                            <div class="callout callout-warning">
                                <h5>Unstable feed condition</h5>
                                <p>Hold ramp rate until process indicators stabilize.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Metadata</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-box mb-3">
                                <span class="info-box-icon bg-info"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">KPI</span>
                                    <span class="info-box-number">readiness_score</span>
                                </div>
                            </div>
                            <div class="info-box mb-0">
                                <span class="info-box-icon bg-success"><i class="fas fa-sliders-h"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">IOW</span>
                                    <span class="info-box-number">startup_ramp_rate</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Related Items</h3>
                        </div>
                        <div class="card-body">
                            <a href="#" class="btn btn-block btn-outline-secondary text-left">
                                <i class="fas fa-book mr-2"></i>Safe Start-up Checklist
                            </a>
                            <a href="#" class="btn btn-block btn-outline-secondary text-left">
                                <i class="fas fa-file-alt mr-2"></i>Operator Shift Handover
                            </a>
                            <a href="#" class="btn btn-block btn-outline-secondary text-left">
                                <i class="fas fa-robot mr-2"></i>Start-up AI Shortcut
                            </a>
                        </div>
                    </div>

                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Layout Hotspot</h3>
                        </div>
                        <div class="card-body">
                            <div class="position-relative border rounded bg-light" style="height: 180px;">
                                <span class="badge badge-danger position-absolute" style="left: 12%; top: 18%;">Overview</span>
                            </div>
                            <p class="text-muted mt-2 mb-0">X: 12, Y: 18</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
