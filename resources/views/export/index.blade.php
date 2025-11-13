@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <h1 class="mb-4">Export Archive</h1>
        <p class="lead text-muted mb-5">Export your entire archive to Omeka-compatible format.</p>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Archive Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h3 class="text-primary">{{ $stats['collections'] }}</h3>
                        <small class="text-muted">Collections</small>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-primary">{{ $stats['items'] }}</h3>
                        <small class="text-muted">Items</small>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-primary">{{ $stats['exhibits'] }}</h3>
                        <small class="text-muted">Exhibits</small>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-primary">{{ $stats['media'] }}</h3>
                        <small class="text-muted">Media Files</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Export to Omeka Classic</h5>
            </div>
            <div class="card-body">
                <p>This export creates a ZIP package containing:</p>
                <ul>
                    <li><strong>items.csv</strong> - All items with full Dublin Core metadata</li>
                    <li><strong>collections.csv</strong> - Collection data</li>
                    <li><strong>exhibit_*.xml</strong> - Exhibit configurations (one per exhibit)</li>
                    <li><strong>files/</strong> - All media files</li>
                    <li><strong>README.txt</strong> - Detailed import instructions</li>
                </ul>

                <div class="alert alert-info">
                    <h6 class="alert-heading">Omeka Requirements</h6>
                    <p class="mb-0">To import this data into Omeka Classic, you'll need:</p>
                    <ul class="mb-0 mt-2">
                        <li>Omeka Classic 2.x or 3.x installation</li>
                        <li>CSV Import plugin (for items and collections)</li>
                        <li>Exhibit Builder plugin (for exhibits)</li>
                    </ul>
                </div>

                <div class="alert alert-warning">
                    <h6 class="alert-heading">Important Notes</h6>
                    <ul class="mb-0">
                        <li>Large archives may take several minutes to export</li>
                        <li>The ZIP file size depends on your media files</li>
                        <li>Exhibits must be manually recreated using the provided XML as reference</li>
                        <li>Review README.txt in the export for detailed import instructions</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('export.omeka') }}" 
                      onsubmit="return confirm('This may take several minutes. Continue?');">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-download"></i>
                        Generate Omeka Export
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Command Line Export</h5>
            </div>
            <div class="card-body">
                <p>You can also generate exports via the command line:</p>
                <pre class="bg-dark text-light p-3 rounded"><code>php artisan export:omeka</code></pre>
                <p class="mb-0 small text-muted">
                    The export file will be saved to <code>storage/app/exports/</code>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
