# Grant Service Account Access to Google Analytics - Detailed Guide

## Overview
This guide explains how to give your Google service account the necessary permissions to access Google Analytics data. Your service account needs "Read & Analyze" permissions to retrieve analytics data for your Laravel backend.

## Prerequisites
- You have created a Google service account and downloaded the JSON credentials file
- You have a Google Analytics property set up
- You are an Administrator of the Google Analytics property

## Step-by-Step Instructions

### Step 1: Locate Your Service Account Email
1. Open your service account JSON credentials file
2. Find the `client_email` field - this is your service account email
3. It looks like: `your-service-account@your-project-id.iam.gserviceaccount.com`
4. Copy this email address

**Example from JSON file:**
```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "...",
  "private_key": "...",
  "client_email": "analytics-service@your-project-id.iam.gserviceaccount.com",
  "client_id": "...",
  ...
}
```

### Step 2: Access Google Analytics Admin
1. Go to [Google Analytics](https://analytics.google.com/)
2. Sign in with your Google account (must be an Administrator)
3. Click on the **Admin** gear icon (⚙️) in the bottom left corner
4. Make sure you're in the correct **Account** and **Property**

### Step 3: Navigate to User Management
1. In the Admin section, you'll see three columns:
   - **Account** (left column)
   - **Property** (middle column) 
   - **Data Stream** (right column)

2. In the **Property** column (middle), click on **Property User Management**
   - Alternative path: Property Settings → Property User Management

### Step 4: Add Service Account User
1. Click the **"+"** (plus) button in the top right
2. Select **"Add users"**

### Step 5: Enter Service Account Details
1. **Email addresses**: Paste your service account email
   ```
   analytics-service@your-project-id.iam.gserviceaccount.com
   ```

2. **Standard roles**: Select the appropriate permission level:

#### Recommended Permission: **Analyst**
- ✅ **Analyst** - Can see configuration and data, create and edit shared assets
- This includes "Read & Analyze" permissions
- Perfect for service accounts that need to read analytics data

#### Alternative Permissions (if Analyst not available):
- ✅ **Viewer** - Can see reports and configuration
- ❌ **Editor** - Too much access for a service account
- ❌ **Administrator** - Unnecessary and risky

### Step 6: Configure Notification Settings
1. **Notify new users by email**: ❌ **Uncheck this** (service accounts don't need email notifications)
2. **Add a message**: Leave blank or add a note like "Service account for website analytics"

### Step 7: Add the User
1. Click **"Add"** button
2. You should see a confirmation message
3. The service account email should now appear in the user list

## Verification Steps

### Verify Access in Analytics
1. Check the **Property User Management** list
2. You should see your service account email with "Analyst" role
3. Status should show as "Active"

### Test API Access (Optional)
You can test if the permissions work by running a simple API call:

```bash
# In your Laravel project directory
php artisan tinker
```

```php
// Test in tinker
$service = app(App\Services\GoogleAnalyticsService::class);
$data = $service->getBasicMetrics();
dd($data);
```

## Troubleshooting Common Issues

### Issue 1: "Service account email not found"
**Solution**: Double-check the email format from your JSON file

### Issue 2: "Insufficient permissions"
**Solutions**:
- Ensure you selected "Analyst" or "Viewer" role
- Make sure you're adding to the correct Property
- Verify you have Administrator access to the Analytics property

### Issue 3: "Access denied" in Laravel
**Solutions**:
- Wait 5-10 minutes for permissions to propagate
- Check your service account JSON file is correctly configured in Laravel
- Verify the Google Analytics Property ID is correct

### Issue 4: Can't find "Property User Management"
**Solutions**:
- Make sure you're in the **Property** column (middle column)
- Check you have Administrator access
- Try refreshing the page or switching between properties

## Important Notes

### Security Best Practices
- ✅ Only grant minimum necessary permissions (Analyst/Viewer)
- ✅ Use descriptive names for service accounts
- ✅ Regularly audit service account access
- ❌ Never give Administrator access to service accounts
- ❌ Don't share service account credentials

### Permission Levels Explained
| Role | Can Read Data | Can Edit Config | Can Manage Users | Recommended |
|------|---------------|-----------------|------------------|-------------|
| **Viewer** | ✅ | ❌ | ❌ | ✅ Good |
| **Analyst** | ✅ | ✅ (Limited) | ❌ | ✅ **Best** |
| **Editor** | ✅ | ✅ | ❌ | ⚠️ Too much |
| **Administrator** | ✅ | ✅ | ✅ | ❌ Risky |

## What Happens After Adding Access

### Immediate Effects
- Service account can authenticate with Google Analytics API
- Your Laravel backend can fetch analytics data
- No email notifications sent to service account

### Data Available
With "Analyst" permissions, your service account can access:
- ✅ Page views and sessions
- ✅ User demographics (if configured)
- ✅ Traffic sources
- ✅ Device and browser data
- ✅ Real-time data
- ✅ Custom dimensions and metrics
- ❌ Advanced segments (limited)
- ❌ Data export/import

## Next Steps After Granting Access

1. **Test Your Laravel Integration**
   ```bash
   php artisan analytics:test
   ```

2. **Set Up Scheduled Data Fetching**
   ```bash
   php artisan schedule:work
   ```

3. **Configure Caching** (if not already done)
   - Set appropriate cache TTL for analytics data
   - Consider Redis for better performance

4. **Monitor Usage**
   - Check Google Cloud Console for API usage
   - Monitor for any authentication errors

## Related Files in Your Laravel Project

After granting access, these files should work properly:
- `app/Services/GoogleAnalyticsService.php`
- `app/Http/Controllers/API/AnalyticsController.php`
- Your service account JSON file in `storage/app/google/`

## Support Resources

- [Google Analytics Admin Help](https://support.google.com/analytics/answer/1009702)
- [Service Account Documentation](https://cloud.google.com/iam/docs/service-accounts)
- [Analytics Reporting API Guide](https://developers.google.com/analytics/devguides/reporting/data/v1)

That's it! Your service account should now have the necessary permissions to access Google Analytics data for your Laravel application.