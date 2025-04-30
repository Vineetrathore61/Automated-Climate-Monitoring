<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', ['city' => 'Delhi']);
    }

    public function fetchClimateData(Request $request)
    {
        $city = $request->input('city', 'Delhi');
        $apiKey = env('OPENWEATHER_API_KEY');

        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
        $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$apiKey}&units=metric";

        $weatherResponse = Http::get($weatherUrl);
        $forecastResponse = Http::get($forecastUrl);

        if ($weatherResponse->failed() || !isset($weatherResponse['main'])) {
            return response()->json([
                'temperature' => 'N/A',
                'humidity' => 'N/A',
                'weather' => 'API Error',
                'aqi' => 'N/A',
                'forecast' => [],
                'rain' => 'N/A'
            ]);
        }

        $data = $weatherResponse->json();
        $forecastData = $forecastResponse->json();
        $lat = $data['coord']['lat'];
        $lon = $data['coord']['lon'];

        $aqiUrl = "https://api.openweathermap.org/data/2.5/air_pollution?lat={$lat}&lon={$lon}&appid={$apiKey}";
        $aqiResponse = Http::get($aqiUrl);
        $aqi = $aqiResponse['list'][0]['main']['aqi'] ?? null;

        $rain = $data['rain']['1h'] ?? ($data['rain']['3h'] ?? 'No rain');

        $forecast = collect($forecastData['list'])->map(function ($item) {
            return [
                'datetime' => $item['dt_txt'],
                'temp' => $item['main']['temp']
            ];
        });

        return response()->json([
            'temperature' => $data['main']['temp'] ?? 'N/A',
            'humidity' => $data['main']['humidity'] ?? 'N/A',
            'weather' => $data['weather'][0]['main'] ?? 'N/A',
            'aqi' => $aqi ?? 'N/A',
            'forecast' => $forecast,
            'rain' => is_numeric($rain) ? "$rain mm" : $rain,
            'lat' => $lat,
            'lon' => $lon,
        ]);
    }

    public function downloadPDF(Request $request)
    {
        $city = $request->input('city', 'Delhi');
        $apiKey = env('OPENWEATHER_API_KEY');

        $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
        $response = Http::get($url);

        if ($response->failed() || !isset($response['main'])) {
            $weather = [
                'city' => $city,
                'temperature' => 'N/A',
                'humidity' => 'N/A',
                'weather' => 'API Error',
                'aqi' => 'N/A',
                'rain' => 'N/A'
            ];
        } else {
            $data = $response->json();
            $lat = $data['coord']['lat'];
            $lon = $data['coord']['lon'];

            $aqiUrl = "https://api.openweathermap.org/data/2.5/air_pollution?lat={$lat}&lon={$lon}&appid={$apiKey}";
            $aqiResponse = Http::get($aqiUrl);
            $aqi = $aqiResponse['list'][0]['main']['aqi'] ?? 'N/A';

            $rain = $data['rain']['1h'] ?? ($data['rain']['3h'] ?? 'No rain');

            $weather = [
                'city' => $data['name'] ?? $city,
                'temperature' => $data['main']['temp'] ?? 'N/A',
                'humidity' => $data['main']['humidity'] ?? 'N/A',
                'weather' => $data['weather'][0]['main'] ?? 'N/A',
                'aqi' => $aqi,
                'rain' => is_numeric($rain) ? "$rain mm" : $rain,
            ];
        }

        $pdf = Pdf::loadView('dashboard_pdf', compact('weather'));
        return $pdf->download('climate_dashboard.pdf');
    }

    public function autocompleteCity(Request $request)
    {
        $search = $request->query('query');
        $apiKey = env('OPENWEATHER_API_KEY');

        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $geoUrl = "http://api.openweathermap.org/geo/1.0/direct?q={$search}&limit=5&appid={$apiKey}";
        $geoResponse = Http::get($geoUrl);

        if ($geoResponse->failed()) {
            return response()->json([]);
        }

        $cities = collect($geoResponse->json())->map(function ($city) {
            return $city['name'] . (isset($city['state']) ? ", " . $city['state'] : "") . ", " . $city['country'];
        });

        return response()->json($cities);
    }
}
