@extends('layouts.master')

@section('title', 'Edit Handbook Article')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Handbook Article</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Admin</a></li>
                        <li class="breadcrumb-item"><a href="#">Handbook</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Synthesis Loop Pressure Monitoring</h3>
                        </div>
                        <form>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label>Title</label>
                                        <input type="text" class="form-control" value="Synthesis Loop Pressure Monitoring">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Status</label>
                                        <select class="form-control">
                                            <option selected>Published</option>
                                            <option>Draft</option>
                                            <option>Archived</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Summary</label>
                                    <textarea class="form-control" rows="3">Demo handbook article for pressure-window monitoring and troubleshooting.</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Content</label>
                                    <textarea class="form-control" rows="7">This article demonstrates KPI and troubleshooting metadata attached to a published handbook article.</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Failure Modes</label>
                                    <textarea class="form-control" rows="4">Pressure drift: check recycle compressor performance and downstream restriction indicators.</textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>AI Shortcut Prompt</label>
                                        <input type="text" class="form-control" value="pressure-troubleshooting">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>View Count</label>
                                        <input type="number" class="form-control" value="94">
                                    </div>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" checked>
                                    <label class="form-check-label">AI trainable</label>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-primary">Update</button>
                                <button type="button" class="btn btn-success">Publish</button>
                                <button type="button" class="btn btn-outline-danger">Archive</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Metadata Blocks</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><span class="badge badge-info">Troubleshooting</span></td>
                                        <td>pressure_drift</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-primary">Equipment Spec</span></td>
                                        <td>recycle_compressor</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-success">KPI</span></td>
                                        <td>loop_stability</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Related Links</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-link mr-2"></i>Pressure Monitoring Notes</li>
                                <li><i class="fas fa-calculator mr-2"></i>Loop Efficiency Calculator</li>
                                <li><i class="fas fa-robot mr-2"></i>AI Troubleshooting Shortcut</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Layout Hotspot</h3>
                        </div>
                        <div class="card-body">
                            <div class="border rounded bg-light position-relative" style="height: 160px;">
                                <span class="badge badge-danger position-absolute" style="left: 54%; top: 42%;">Loop</span>
                            </div>
                            <div class="form-row mt-3">
                                <div class="col">
                                    <input type="number" class="form-control" value="54">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" value="42">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
