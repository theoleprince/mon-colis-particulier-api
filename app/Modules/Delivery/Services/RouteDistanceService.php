<?php

namespace App\Modules\Delivery\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Road distance between two points: OSRM itinerary when available (same engine
 * as the app map), otherwise straight line × a town coefficient.
 */
class RouteDistanceService
{
    /**
     * @return array{km: float, source: 'osrm'|'straight'}
     */
    public function between(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        if (config('moncolis.delivery.routing') === 'osrm') {
            $km = $this->osrm($fromLat, $fromLng, $toLat, $toLng);
            if ($km !== null) {
                return ['km' => round($km, 2), 'source' => 'osrm'];
            }
        }

        $km = self::haversineKm($fromLat, $fromLng, $toLat, $toLng) * config('moncolis.delivery.straight_line_factor');

        return ['km' => round($km, 2), 'source' => 'straight'];
    }

    public static function haversineKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function osrm(float $fromLat, float $fromLng, float $toLat, float $toLng): ?float
    {
        $url = rtrim(config('moncolis.delivery.osrm_url'), '/')
            ."/route/v1/driving/{$fromLng},{$fromLat};{$toLng},{$toLat}";

        try {
            $response = Http::timeout(4)->get($url, ['overview' => 'false']);
            $meters = $response->successful() ? $response->json('routes.0.distance') : null;

            return is_numeric($meters) ? $meters / 1000 : null;
        } catch (Throwable $e) {
            Log::warning('OSRM unavailable, straight line used', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
