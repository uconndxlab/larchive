<?php

namespace App\Http\Controllers;

use App\Services\OmekaExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    /**
     * Show export options page
     */
    public function index()
    {
        $stats = [
            'collections' => \App\Models\Collection::count(),
            'items' => \App\Models\Item::count(),
            'exhibits' => \App\Models\Exhibit::count(),
            'media' => \App\Models\Media::count(),
        ];

        return view('export.index', compact('stats'));
    }

    /**
     * Generate and download Omeka export
     */
    public function omeka()
    {
        try {
            set_time_limit(300); // 5 minutes for large exports
            
            $exporter = new OmekaExporter();
            $zipPath = $exporter->exportAll();

            return Response::download($zipPath, basename($zipPath), [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
