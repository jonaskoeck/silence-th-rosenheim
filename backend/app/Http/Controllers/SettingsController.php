<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function updateSchedulePollInterval(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'minutes' => ['required', 'integer', Rule::in(Setting::ALLOWED_SCHEDULE_POLL_INTERVAL_MINUTES)],
        ]);

        Setting::set(
            Setting::KEY_SCHEDULE_POLL_INTERVAL_MINUTES,
            (string) $validated['minutes'],
        );

        if ($request->wantsJson() || $request->header('HX-Request')) {
            return response()->json([
                'schedule_poll_interval_minutes' => (int) $validated['minutes'],
            ]);
        }

        return redirect()->back();
    }

    public function updateInventoryInterval(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'minutes' => ['required', 'integer', Rule::in(Setting::ALLOWED_INVENTORY_INTERVAL_MINUTES)],
        ]);

        Setting::set(
            Setting::KEY_INVENTORY_INTERVAL_MINUTES,
            (string) $validated['minutes'],
        );

        if ($request->wantsJson() || $request->header('HX-Request')) {
            return response()->json([
                'inventory_interval_minutes' => (int) $validated['minutes'],
            ]);
        }

        return redirect()->back();
    }
}
