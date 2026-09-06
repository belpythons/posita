<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switches which of the user's outlets the web session works in.
 *
 * The chosen outlet is stored in the session, not the URL, and is validated
 * against the outlets the user actually belongs to.
 */
class OutletSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'outlet_id' => ['required', 'integer'],
        ]);

        $outlet = $request->user()->outlets()->whereKey($validated['outlet_id'])->first();

        abort_if($outlet === null, 403, __('tenancy.outlet_forbidden'));

        $request->session()->put('outlet_id', $outlet->getKey());

        return back();
    }
}
