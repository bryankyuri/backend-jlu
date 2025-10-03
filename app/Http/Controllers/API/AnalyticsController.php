<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsService;
use Illuminate\Http\Request;
use Exception;

class AnalyticsController extends Controller
{
    private $analyticsService;

    public function __construct(GoogleAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get analytics data for dashboard
     */
    public function getDashboardData(Request $request)
    {
        try {
            $period = $request->get('period', 'last30days');
            
            // Validate period
            $validPeriods = ['last7days', 'last30days', 'last90days'];
            if (!in_array($period, $validPeriods)) {
                $period = 'last30days';
            }

            $data = $this->analyticsService->getAnalyticsData($period);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearCache()
    {
        try {
            $this->analyticsService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Analytics cache cleared successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}