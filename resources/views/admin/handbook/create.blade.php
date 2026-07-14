@extends('layouts.master')

@section('title', 'Create Handbook Article')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create Handbook Article</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Admin</a></li>
                        <li class="breadcrumb-item"><a href="#">Handbook</a></li>
                        <li class="breadcrumb-item active">Create</li>
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
                            <h3 class="card-title">Article Content</h3>
                        </div>
                        <form>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" class="form-control" value="Compressor Seal Gas Monitoring">
                                </div>
                                <div class="form-group">
                                    <label>Slug</label>
                                    <input type="text" class="form-control" value="compressor-seal-gas-monitoring">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Category</label>
                                        <select class="form-control">
                                            <option>Synthesis Loop</option>
                                            <option>Plant Overview</option>
                                            <option>Utility Systems</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Status</label>
                                        <select class="form-control">
                                            <option>Draft</option>
                                            <option>Published</option>
                                            <option>Archived</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Summary</label>
                                    <textarea class="form-control" rows="3">Demo operating note for seal gas trends, alarm limits, and response steps.</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Content</label>
                                    <textarea class="form-control" rows="8">Use this static smockup text to preview how a handbook article will appear in the admin workflow before data binding is added.</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Optimization Guidance</label>
                                    <textarea class="form-control" rows="3">Trend seal gas pressure against compressor load and alarm events.</textarea>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" checked>
                                    <label class="form-check-label">AI trainable after approval</label>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-primary">Save Draft</button>
                                <button type="button" class="btn btn-outline-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Metadata</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Type</label>
                                <select class="form-control">
                                    <option>KPI</option>
                                    <option>IOW</option>
                                    <option>Troubleshooting</option>
                                    <option>Equipment Spec</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Key</label>
                                <input type="text" class="form-control" value="seal_gas_pressure">
                            </div>
                            <div class="form-group">
                                <label>Value</label>
                                <textarea class="form-control" rows="3">Maintain within approved operating window.</textarea>
                            </div>
                            <button type="button" class="btn btn-sm btn-info">Add Metadata</button>
                        </div>
                    </div>

                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Related Items</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Library Item</label>
                                <select class="form-control">
                                    <option>Safe Start-up Checklist</option>
                                    <option>Pressure Monitoring Notes</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary">Attach Link</button>
                        </div>
                    </div>

                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Hotspot</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label>X</label>
                                    <input type="number" class="form-control" value="54">
                                </div>
                                <div class="form-group col-6">
                                    <label>Y</label>
                                    <input type="number" class="form-control" value="42">
                                </div>
                            </div>
                            <div class="border rounded bg-light position-relative" style="height: 140px;">
                                <span class="badge badge-danger position-absolute" style="left: 54%; top: 42%;">Preview</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
