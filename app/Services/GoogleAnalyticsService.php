<?php

namespace App\Services;

use Google\Client;
use Google\Service\AnalyticsReporting;
use Google\Service\AnalyticsReporting\DateRange;
use Google\Service\AnalyticsReporting\Metric;
use Google\Service\AnalyticsReporting\Dimension;
use Google\Service\AnalyticsReporting\ReportRequest;
use Google\Service\AnalyticsReporting\GetReportsRequest;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleAnalyticsService
{
    private $client;
    private $analytics;
    private $viewId;

    public function __construct()
    {
        $this->initializeClient();
        $this->viewId = config('services.google_analytics.view_id');
    }

    private function initializeClient()
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName(config('app.name'));
            $this->client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
            
            // Use service account credentials
            $credentialsPath = config('services.google_analytics.service_account_path');
            if ($credentialsPath && file_exists($credentialsPath)) {
                $this->client->setAuthConfig($credentialsPath);
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
                $this->client->setAuthConfig($credentials);
            }

            $this->analytics = new AnalyticsReporting($this->client);
        } catch (Exception $e) {
            Log::error('Google Analytics initialization failed: ' . $e->getMessage());
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
                Log::error('Failed to fetch analytics data: ' . $e->getMessage());
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
        $dateRangeObj = new DateRange();
        $dateRangeObj->setStartDate($dateRange['startDate']);
        $dateRangeObj->setEndDate($dateRange['endDate']);

        $metrics = [
            new Metric(['expression' => 'ga:users']),
            new Metric(['expression' => 'ga:pageviews']),
            new Metric(['expression' => 'ga:sessions']),
            new Metric(['expression' => 'ga:bounceRate']),
            new Metric(['expression' => 'ga:avgSessionDuration']),
        ];

        $request = new ReportRequest();
        $request->setViewId($this->viewId);
        $request->setDateRanges($dateRangeObj);
        $request->setMetrics($metrics);

        $body = new GetReportsRequest();
        $body->setReportRequests([$request]);

        $response = $this->analytics->reports->batchGet($body);
        $report = $response->getReports()[0];
        $rows = $report->getData()->getRows();

        if (empty($rows)) {
            return [];
        }

        $values = $rows[0]->getMetrics()[0]->getValues();
        
        return [
            'users' => (int) $values[0],
            'pageviews' => (int) $values[1],
            'sessions' => (int) $values[2],
            'bounceRate' => round($values[3], 2) . '%',
            'avgSessionDuration' => $this->formatDuration($values[4])
        ];
    }

    private function getTopPages($dateRange)
    {
        $dateRangeObj = new DateRange();
        $dateRangeObj->setStartDate($dateRange['startDate']);
        $dateRangeObj->setEndDate($dateRange['endDate']);

        $metrics = [new Metric(['expression' => 'ga:pageviews'])];
        $dimensions = [new Dimension(['name' => 'ga:pagePath'])];

        $request = new ReportRequest();
        $request->setViewId($this->viewId);
        $request->setDateRanges($dateRangeObj);
        $request->setMetrics($metrics);
        $request->setDimensions($dimensions);
        $request->setOrderBys([
            ['fieldName' => 'ga:pageviews', 'sortOrder' => 'DESCENDING']
        ]);
        $request->setPageSize(10);

        $body = new GetReportsRequest();
        $body->setReportRequests([$request]);

        $response = $this->analytics->reports->batchGet($body);
        $report = $response->getReports()[0];
        $rows = $report->getData()->getRows();

        $topPages = [];
        if ($rows) {
            foreach ($rows as $row) {
                $topPages[] = [
                    'path' => $row->getDimensions()[0],
                    'views' => (int) $row->getMetrics()[0]->getValues()[0]
                ];
            }
        }

        return $topPages;
    }

    private function getTrafficSources($dateRange)
    {
        $dateRangeObj = new DateRange();
        $dateRangeObj->setStartDate($dateRange['startDate']);
        $dateRangeObj->setEndDate($dateRange['endDate']);

        $metrics = [new Metric(['expression' => 'ga:sessions'])];
        $dimensions = [new Dimension(['name' => 'ga:source'])];

        $request = new ReportRequest();
        $request->setViewId($this->viewId);
        $request->setDateRanges($dateRangeObj);
        $request->setMetrics($metrics);
        $request->setDimensions($dimensions);
        $request->setOrderBys([
            ['fieldName' => 'ga:sessions', 'sortOrder' => 'DESCENDING']
        ]);
        $request->setPageSize(10);

        $body = new GetReportsRequest();
        $body->setReportRequests([$request]);

        $response = $this->analytics->reports->batchGet($body);
        $report = $response->getReports()[0];
        $rows = $report->getData()->getRows();

        $sources = [];
        if ($rows) {
            foreach ($rows as $row) {
                $sources[] = [
                    'source' => $row->getDimensions()[0],
                    'sessions' => (int) $row->getMetrics()[0]->getValues()[0]
                ];
            }
        }

        return $sources;
    }

    private function getDailyVisitors($dateRange)
    {
        $dateRangeObj = new DateRange();
        $dateRangeObj->setStartDate($dateRange['startDate']);
        $dateRangeObj->setEndDate($dateRange['endDate']);

        $metrics = [new Metric(['expression' => 'ga:users'])];
        $dimensions = [new Dimension(['name' => 'ga:date'])];

        $request = new ReportRequest();
        $request->setViewId($this->viewId);
        $request->setDateRanges($dateRangeObj);
        $request->setMetrics($metrics);
        $request->setDimensions($dimensions);
        $request->setOrderBys([
            ['fieldName' => 'ga:date', 'sortOrder' => 'ASCENDING']
        ]);

        $body = new GetReportsRequest();
        $body->setReportRequests([$request]);

        $response = $this->analytics->reports->batchGet($body);
        $report = $response->getReports()[0];
        $rows = $report->getData()->getRows();

        $dailyData = [];
        if ($rows) {
            foreach ($rows as $row) {
                $date = $row->getDimensions()[0];
                $formattedDate = \Carbon\Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');
                
                $dailyData[] = [
                    'date' => $formattedDate,
                    'visitors' => (int) $row->getMetrics()[0]->getValues()[0]
                ];
            }
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