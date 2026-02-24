<?php

namespace App\Http\Controllers;
use App\Models\Language;

use App\Services\LocationService;

use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $locationService;

    public function __construct(Request $request, LocationService $locationService){
        $this->locationService = $locationService;
        app()->setLocale($request->header('Accept-Language'));
    }

    public function get(Request $request){
        $language = Language::where('lang_tag', '=', $request->header('Accept-Language'))->first();

        $locations = $this->locationService->get_locations($language);

        return response()->json($locations, 200);
    }
}
