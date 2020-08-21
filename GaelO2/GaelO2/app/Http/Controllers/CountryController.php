<?php

namespace App\Http\Controllers;

use App;
use Illuminate\Http\Request;
use App\GaelO\UseCases\GetCountry\GetCountry;
use App\GaelO\UseCases\GetCountry\GetCountryRequest;
use App\GaelO\UseCases\GetCountry\GetCountryResponse;

class CountryController extends Controller
{
    public function getCountry(string $code='', GetCountryRequest $getCountryRequest, GetCountryResponse $getCountryResponse) {
        $getCountryRequest->code = $code;
        $getCountry = App::make('GetCountry');
        $getCountry->execute($getCountryRequest, $getCountryResponse);
        return response()->json($getCountryResponse->body, $getCountryResponse->status);
    }
}