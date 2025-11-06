<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\OhmsImporter;
use Illuminate\Http\Request;

class ItemOhmsController extends Controller
{
    protected OhmsImporter $importer;

    public function __construct(OhmsImporter $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Store OHMS XML for an item.
     */
    public function store(Request $request, Item $item)
    {
        $request->validate([
            'ohms_xml' => 'required|file|mimes:xml,txt|max:10240', // 10MB max
        ]);

        try {
            // Store uploaded file temporarily
            $file = $request->file('ohms_xml');
            $tempPath = $file->getRealPath();

            // Import OHMS data
            $this->importer->import($item, $tempPath);

            return redirect()
                ->route('items.edit', $item)
                ->with('success', 'OHMS XML imported successfully');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to import OHMS XML: ' . $e->getMessage());
        }
    }
}
