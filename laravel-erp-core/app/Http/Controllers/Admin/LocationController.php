<?php

namespace App\Http\Controllers\Admin;

use App\Core\Location\CountryStateData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function states(Request $request, CountryStateData $locations): JsonResponse
    {
        $data = $request->validate([
            'country' => ['required', 'string', 'size:2'],
        ]);

        return response()->json($locations->states($data['country']));
    }
}
