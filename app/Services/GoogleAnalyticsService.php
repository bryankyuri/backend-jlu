<?php

namespace App\Services;

use Google\Client;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Exception;
use Illuminate\Support\Facades\Cache;

class GoogleAnalyticsService
{
    private $client;
    private $analytics;
    private $propertyId;

    public function __construct()
    {
        $this->initializeClient();
        $this->propertyId = 'properties/' . config('services.google_analytics.property_id');
    }

    private function initializeClient()
    {
        try {
            // Use service account credentials
            $credentialsPath = config('services.google_analytics.service_account_path');
            if ($credentialsPath && file_exists($credentialsPath)) {
                $this->analytics = new BetaAnalyticsDataClient([
                    'credentials' => $credentialsPath
                ]);
            } else {
                // Use environment variables if no file
                $credentials = [
                    'type' => 'service_account',
                    'project_id' => config('services.google_analytics.project_id'),
                    'private_key_id' => config('services.google_analytics.private_key_id'),
                    'private_key' => str_replace('\\n', "\n", config('services.google_analytics.private_key')),
                    'client_email' => config('services.google_analytics.client_email'),
                    'client_id' => config('services.google_analytics.client_id'),
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                ];
                
                $this->analytics = new BetaAnalyticsDataClient([
                    'credentials' => $credentials
                ]);
            }
        } catch (Exception $e) {
            // Remove Log facade usage to avoid facade errors
            error_log('Google Analytics initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAnalyticsData($period = 'last30days')
    {
        $cacheKey = "analytics_data_{$period}";
        
        return Cache::remember($cacheKey, 3600, function () use ($period) {
            try {
                $dateRange = $this->getDateRange($period);
                
                // Get basic metrics
                $basicMetrics = $this->getBasicMetrics($dateRange);
                $topPages = $this->getTopPages($dateRange);
                $trafficSources = $this->getTrafficSources($dateRange);
                $dailyVisitors = $this->getDailyVisitors($dateRange);

                return [
                    'visitors' => $basicMetrics['users'] ?? 0,
                    'pageViews' => $basicMetrics['pageviews'] ?? 0,
                    'sessions' => $basicMetrics['sessions'] ?? 0,
                    'bounceRate' => $basicMetrics['bounceRate'] ?? '0%',
                    'avgSessionDuration' => $basicMetrics['avgSessionDuration'] ?? '0s',
                    'topPages' => $topPages,
                    'trafficSources' => $trafficSources,
                    'dailyVisitors' => $dailyVisitors,
                    'period' => $period,
                    'lastUpdated' => now()->toISOString()
                ];
            } catch (Exception $e) {
                error_log('Failed to fetch analytics data: ' . $e->getMessage());
                return $this->getFallbackData();
            }
        });
    }

    private function getDateRange($period)
    {
        switch ($period) {
            case 'last7days':
                return ['startDate' => '7daysAgo', 'endDate' => 'today'];
            case 'last90days':
                return ['startDate' => '90daysAgo', 'endDate' => 'today'];
            case 'last30days':
            default:
                return ['startDate' => '30daysAgo', 'endDate' => 'today'];
        }
    }

    private function getBasicMetrics($dateRange)
    {
        $request = new RunReportRequest([
            'property' => $this->propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $dateRange['startDate'],
                    'end_date' => $dateRange['endDate'],
                ])
            ],
            'metrics' => [
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
            ]
        ]);

        $response = $this->analytics->runReport($request);
        
        if (!$response->getRows() || count($response->getRows()) === 0) {
            return [];
        }

        $row = $response->getRows()[0];
        $metricValues = $row->getMetricValues();
        
        return [
            'users' => (int) $metricValues[0]->getValue(),
            'pageviews' => (int) $metricValues[1]->getValue(),
            'sessions' => (int) $metricValues[2]->getValue(),
            'bounceRate' => round($metricValues[3]->getValue() * 100, 2) . '%',
            'avgSessionDuration' => $this->formatDuration($metricValues[4]->getValue())
        ];
    }

    private function getTopPages($dateRange)
    {
        $request = new RunReportRequest([
            'property' => $this->propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $dateRange['startDate'],
                    'end_date' => $dateRange['endDate'],
                ])
            ],
            'dimensions' => [
                new Dimension(['name' => 'pagePath'])
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews'])
            ],
            'order_bys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy([
                        'metric_name' => 'screenPageViews'
                    ]),
                    'desc' => true
                ])
            ],
            'limit' => 10
        ]);

        $response = $this->analytics->runReport($request);
        
        $topPages = [];
        foreach ($response->getRows() as $row) {
            $topPages[] = [
                'path' => $row->getDimensionValues()[0]->getValue(),
                'views' => (int) $row->getMetricValues()[0]->getValue()
            ];
        }

        return $topPages;
    }

    private function getTrafficSources($dateRange)
    {
        $request = new RunReportRequest([
            'property' => $this->propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $dateRange['startDate'],
                    'end_date' => $dateRange['endDate'],
                ])
            ],
            'dimensions' => [
                new Dimension(['name' => 'sessionSource'])
            ],
            'metrics' => [
                new Metric(['name' => 'sessions'])
            ],
            'order_bys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy([
                        'metric_name' => 'sessions'
                    ]),
                    'desc' => true
                ])
            ],
            'limit' => 10
        ]);

        $response = $this->analytics->runReport($request);
        
        $sources = [];
        foreach ($response->getRows() as $row) {
            $sources[] = [
                'source' => $row->getDimensionValues()[0]->getValue(),
                'sessions' => (int) $row->getMetricValues()[0]->getValue()
            ];
        }

        return $sources;
    }

    private function getDailyVisitors($dateRange)
    {
        $request = new RunReportRequest([
            'property' => $this->propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $dateRange['startDate'],
                    'end_date' => $dateRange['endDate'],
                ])
            ],
            'dimensions' => [
                new Dimension(['name' => 'date'])
            ],
            'metrics' => [
                new Metric(['name' => 'activeUsers'])
            ],
            'order_bys' => [
                new OrderBy([
                    'dimension' => new DimensionOrderBy([
                        'dimension_name' => 'date'
                    ]),
                    'desc' => false
                ])
            ]
        ]);

        $response = $this->analytics->runReport($request);
        
        $dailyData = [];
        foreach ($response->getRows() as $row) {
            $date = $row->getDimensionValues()[0]->getValue();
            $formattedDate = \Carbon\Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');
            
            $dailyData[] = [
                'date' => $formattedDate,
                'visitors' => (int) $row->getMetricValues()[0]->getValue()
            ];
        }

        return $dailyData;
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    private function getFallbackData()
    {
        return [
            'visitors' => 1250,
            'pageViews' => 4800,
            'sessions' => 1100,
            'bounceRate' => '45.2%',
            'avgSessionDuration' => '2m 15s',
            'topPages' => [
                ['path' => '/', 'views' => 850],
                ['path' => '/about', 'views' => 320],
                ['path' => '/services', 'views' => 280],
                ['path' => '/contact', 'views' => 180],
                ['path' => '/portfolio', 'views' => 150]
            ],
            'trafficSources' => [
                ['source' => 'google', 'sessions' => 680],
                ['source' => 'direct', 'sessions' => 250],
                ['source' => 'facebook', 'sessions' => 120],
                ['source' => 'twitter', 'sessions' => 50]
            ],
            'dailyVisitors' => [],
            'period' => 'fallback',
            'lastUpdated' => now()->toISOString(),
            'isFallback' => true
        ];
    }

    public function clearCache()
    {
        Cache::forget('analytics_data_last7days');
        Cache::forget('analytics_data_last30days');
        Cache::forget('analytics_data_last90days');
    }
}