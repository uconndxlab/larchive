<?php

namespace App\Http\Controllers;

use App\Models\SiteNotice;
use Illuminate\Http\Request;

class SiteNoticeController extends Controller
{
    /**
     * Show the form for editing the site notice.
     */
    public function edit()
    {
        $notice = SiteNotice::instance();
        return view('admin.site-notice.edit', compact('notice'));
    }

    /**
     * Update the site notice.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string',
        ]);

        $notice = SiteNotice::instance();
        $notice->update([
            'enabled' => $request->has('enabled'),
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'] ?? null,
        ]);

        return redirect()
            ->route('admin.site-notice.edit')
            ->with('success', 'Site notice updated successfully.');
    }

    /**
     * Handle acknowledgment of the site notice.
     */
    public function acknowledge()
    {
        // Set a cookie that expires in 1 year
        cookie()->queue('larchive_notice_acknowledged', 'true', 525600);

        return response()->json(['success' => true]);
    }
}
