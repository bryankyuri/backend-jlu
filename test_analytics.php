<?php

require_once 'vendor/autoload.php';

// Load .env file manually since we're not in Laravel context
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;

try {
    echo "Testing Google Analytics Data API with .env credentials...\n";
    
    // Check if environment variables are loaded
    echo "Property ID: " . getenv('GOOGLE_ANALYTICS_PROPERTY_ID') . "\n";
    echo "Project ID: " . getenv('GOOGLE_ANALYTICS_PROJECT_ID') . "\n";
    echo "Client Email: " . getenv('GOOGLE_ANALYTICS_CLIENT_EMAIL') . "\n";
    
    // Set up credentials from environment variables
    $credentials = [
        'type' => 'service_account',
        'project_id' => getenv('GOOGLE_ANALYTICS_PROJECT_ID'),
        'private_key_id' => getenv('GOOGLE_ANALYTICS_PRIVATE_KEY_ID'),
        'private_key' => str_replace('\\n', "\n", getenv('GOOGLE_ANALYTICS_PRIVATE_KEY')),
        'client_email' => getenv('GOOGLE_ANALYTICS_CLIENT_EMAIL'),
        'client_id' => getenv('GOOGLE_ANALYTICS_CLIENT_ID'),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ];
    
    // Initialize the client
    $client = new BetaAnalyticsDataClient([
        'credentials' => $credentials
    ]);
    
    echo "Client initialized successfully!\n";
    
    // Create a simple request to test authentication
    $propertyId = 'properties/' . getenv('GOOGLE_ANALYTICS_PROPERTY_ID');
    $request = new RunReportRequest([
        'property' => $propertyId,
        'dateRanges' => [
            new DateRange([
                'start_date' => '7daysAgo',
                'end_date' => 'today',
            ])
        ],
        'metrics' => [
            new Metric(['name' => 'activeUsers'])
        ]
    ]);
    
    echo "Making request to Google Analytics API...\n";
    
    // Make the request
    $response = $client->runReport($request);
    
    echo "Success! Got response from Google Analytics API\n";
    echo "Number of rows: " . count($response->getRows()) . "\n";
    
    if ($response->getRows() && count($response->getRows()) > 0) {
        $row = $response->getRows()[0];
        $activeUsers = $row->getMetricValues()[0]->getValue();
        echo "Active users (last 7 days): " . $activeUsers . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    if (method_exists($e, 'getMetadata')) {
        echo "Metadata: " . print_r($e->getMetadata(), true) . "\n";
    }
}