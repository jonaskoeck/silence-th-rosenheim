<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegionRequest;
use App\Http\Requests\UpdateRegionRequest;
use App\Models\Region;
use App\Services\Contracts\RegionServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RegionController extends Controller
{
    public function __construct(private RegionServiceInterface $regions) {}

    public function index(): View
    {
        return view('regions', ['regions' => $this->regions->getAll()]);
    }

    public function store(StoreRegionRequest $request): RedirectResponse|Response
    {
        $this->regions->create($request->validated());

        if ($request->header('HX-Request')) {
            return $this->regionsPartial('Region wurde hinzugefügt.');
        }

        return redirect()->route('regions');
    }

    public function update(UpdateRegionRequest $request, Region $region): RedirectResponse|Response
    {
        $this->regions->update($region, $request->validated());

        if ($request->header('HX-Request')) {
            return $this->regionsPartial("Region \"{$region->code}\" wurde aktualisiert.");
        }

        return redirect()->route('regions');
    }

    public function destroy(Request $request, Region $region): RedirectResponse|Response
    {
        if ($region->projects()->exists()) {
            $message = "Region \"{$region->code}\" hat noch zugeordnete Projekte und kann nicht gelöscht werden.";

            if ($request->header('HX-Request')) {
                return response()->noContent(422)->header(
                    'HX-Trigger',
                    json_encode(['toast' => ['message' => $message, 'type' => 'danger']])
                );
            }

            return redirect()->route('regions')->with('region_error', $message);
        }

        $this->regions->delete($region);

        if ($request->header('HX-Request')) {
            return $this->regionsPartial("Region \"{$region->code}\" wurde gelöscht.");
        }

        return redirect()->route('regions');
    }

    private function regionsPartial(string $toastMessage, string $toastType = 'success'): Response
    {
        $regions = $this->regions->getAll();

        return response(view('partials.regions-list', compact('regions')))
            ->header('HX-Trigger', json_encode(['toast' => ['message' => $toastMessage, 'type' => $toastType]]));
    }
}
