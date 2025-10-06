# Google Analytics Environment Variables Configuration Guide

## Required Environment Variables for Google Analytics

I've added the necessary Google Analytics configuration to your `.env` file. Here's what you need to configure:

### 1. Google Analytics Property Information

```bash
# Your Google Analytics 4 Property ID (required)
GOOGLE_ANALYTICS_PROPERTY_ID=123456789

# Legacy Universal Analytics View ID (optional, for GA3 compatibility)
GOOGLE_ANALYTICS_VIEW_ID=987654321

# Google Cloud Project ID (required)
GOOGLE_ANALYTICS_PROJECT_ID=your-project-id
```

**How to find these values:**

#### Property ID (GA4):
1. Go to Google Analytics → Admin
2. In Property column → Property Settings
3. Copy the "Property ID" (looks like: 123456789)

#### View ID (Universal Analytics - if needed):
1. Go to Google Analytics → Admin
2. In View column → View Settings
3. Copy the "View ID" (looks like: 987654321)

#### Project ID:
1. Go to Google Cloud Console
2. Select your project
3. Copy the Project ID from the dashboard

### 2. Service Account Configuration

```bash
# Path to your service account JSON file (required)
GOOGLE_SERVICE_ACCOUNT_PATH=storage/app/google/service-account.json

# Service account email (required)
GOOGLE_SERVICE_ACCOUNT_EMAIL=your-service@project-id.iam.gserviceaccount.com

# Service account client ID (required)
GOOGLE_SERVICE_ACCOUNT_CLIENT_ID=123456789012345678901

# Service account private key ID (required)
GOOGLE_SERVICE_ACCOUNT_KEY_ID=abc123def456...

# Service account private key (required)
GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkq...\n-----END PRIVATE KEY-----\n"
```

**How to get these values:**

1. **Download Service Account JSON**: From Google Cloud Console → IAM & Admin → Service Accounts
2. **Extract values from JSON file**:

```json
{
  "type": "service_account",
  "project_id": "your-project-id",                    → GOOGLE_ANALYTICS_PROJECT_ID
  "private_key_id": "abc123def456...",               → GOOGLE_SERVICE_ACCOUNT_KEY_ID
  "private_key": "-----BEGIN PRIVATE KEY-----\n...", → GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY
  "client_email": "service@project.iam.gserviceaccount.com", → GOOGLE_SERVICE_ACCOUNT_EMAIL
  "client_id": "123456789012345678901",              → GOOGLE_SERVICE_ACCOUNT_CLIENT_ID
}
```

### 3. Optional Configuration

```bash
# Cache lifetime for analytics data in seconds (default: 1 hour)
GOOGLE_ANALYTICS_CACHE_LIFETIME=3600

# Enable/disable Google Analytics integration
GOOGLE_ANALYTICS_ENABLED=true
```

## Configuration Methods

### Method 1: Individual Environment Variables (Recommended)

Set each value separately in your `.env` file:

```bash
GOOGLE_ANALYTICS_PROPERTY_ID=123456789
GOOGLE_ANALYTICS_PROJECT_ID=your-project-id
GOOGLE_SERVICE_ACCOUNT_EMAIL=analytics@your-project.iam.gserviceaccount.com
GOOGLE_SERVICE_ACCOUNT_CLIENT_ID=123456789012345678901
GOOGLE_SERVICE_ACCOUNT_KEY_ID=abc123def456ghi789
GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC8Q7HgL...
-----END PRIVATE KEY-----"
```

**Advantages:**
- ✅ More secure (no JSON file needed)
- ✅ Better for production environments
- ✅ Environment-specific configuration

### Method 2: JSON File Path (Alternative)

Store the JSON file and reference it:

```bash
GOOGLE_SERVICE_ACCOUNT_PATH=storage/app/google/service-account.json
```

**Steps:**
1. Place your service account JSON file in `storage/app/google/service-account.json`
2. Make sure the file is readable: `chmod 644 storage/app/google/service-account.json`

## Complete .env Example

Here's how your `.env` should look with real values:

```bash
# Google Analytics Configuration
GOOGLE_ANALYTICS_PROPERTY_ID=123456789
GOOGLE_ANALYTICS_VIEW_ID=987654321
GOOGLE_ANALYTICS_PROJECT_ID=my-analytics-project
GOOGLE_SERVICE_ACCOUNT_PATH=storage/app/google/service-account.json
GOOGLE_SERVICE_ACCOUNT_EMAIL=analytics-service@my-analytics-project.iam.gserviceaccount.com
GOOGLE_SERVICE_ACCOUNT_CLIENT_ID=123456789012345678901
GOOGLE_SERVICE_ACCOUNT_KEY_ID=abc123def456ghi789jkl012
GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC8Q7HgL...
(your actual private key content)
...
-----END PRIVATE KEY-----"
GOOGLE_ANALYTICS_CACHE_LIFETIME=3600
GOOGLE_ANALYTICS_ENABLED=true
```

## Security Best Practices

### For Development:
- ✅ Use JSON file method for easier setup
- ✅ Add `storage/app/google/` to `.gitignore`
- ✅ Never commit credentials to version control

### For Production:
- ✅ Use individual environment variables
- ✅ Store in secure environment variable service
- ✅ Use encrypted storage for private keys
- ❌ Never store credentials in plain text files

## Usage in Laravel Code

After configuration, you can access these values in your Laravel application:

```php
// In GoogleAnalyticsService.php
$propertyId = config('services.google_analytics.property_id');
$clientEmail = config('services.google_analytics.client_email');
$privateKey = config('services.google_analytics.private_key');

// Check if analytics is enabled
if (config('services.google_analytics.enabled')) {
    // Initialize analytics service
}
```

## Testing Your Configuration

Create a simple Artisan command to test your setup:

```bash
php artisan make:command TestGoogleAnalytics
```

```php
// In the command
public function handle()
{
    $config = config('services.google_analytics');
    
    $this->info('Google Analytics Configuration:');
    $this->line('Property ID: ' . $config['property_id']);
    $this->line('Project ID: ' . $config['project_id']);
    $this->line('Client Email: ' . $config['client_email']);
    $this->line('Enabled: ' . ($config['enabled'] ? 'Yes' : 'No'));
    
    // Test authentication
    try {
        $service = app(App\Services\GoogleAnalyticsService::class);
        $data = $service->getBasicMetrics();
        $this->info('✅ Google Analytics connection successful!');
    } catch (\Exception $e) {
        $this->error('❌ Google Analytics connection failed: ' . $e->getMessage());
    }
}
```

## Troubleshooting

### Common Issues:

1. **Invalid Private Key Format**
   - Ensure private key includes `\n` for line breaks
   - Wrap in double quotes in .env file

2. **File Permission Issues**
   - Check JSON file permissions: `chmod 644 storage/app/google/service-account.json`
   - Ensure storage directory exists: `mkdir -p storage/app/google`

3. **Property ID Not Found**
   - Verify you're using GA4 Property ID, not Universal Analytics
   - Check you have access to the correct Analytics property

4. **Authentication Errors**
   - Verify service account has been granted access in Google Analytics
   - Check all required fields are filled in

Your Google Analytics integration should now be properly configured! The service will automatically use these environment variables for authentication and data retrieval.