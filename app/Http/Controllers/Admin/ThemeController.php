<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    /**
     * Show the theme settings form.
     */
    public function edit()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $activeTheme = Theme::active();
        $availableThemes = Theme::all();

        return view('admin.settings.theme', compact('activeTheme', 'availableThemes'));
    }

    /**
     * Update the active theme.
     */
    public function update(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'theme' => 'required|string',
        ]);

        // Verify theme exists in config
        if (!Theme::exists($validated['theme'])) {
            return back()->withErrors(['theme' => 'Invalid theme selected.']);
        }

        SiteSetting::set('active_theme', $validated['theme']);

        return redirect()->route('admin.settings.theme')
            ->with('success', 'Theme updated successfully. You may need to refresh to see changes.');
    }
}
