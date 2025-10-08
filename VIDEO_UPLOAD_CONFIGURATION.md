# PHP Configuration for 100MB Video Uploads

This document outlines the PHP configuration changes required to support 100MB video uploads in the Laravel backend.

## Required PHP.ini Settings

To support 100MB video uploads, the following PHP configuration settings must be updated in your `php.ini` file:

### Upload Settings
```ini
; Maximum allowed size for uploaded files (set to 128MB to allow some overhead)
upload_max_filesize = 128M

; Maximum size of POST data that PHP will accept (should be larger than upload_max_filesize)
post_max_size = 128M

; Maximum amount of memory a script may consume (should be larger than post_max_size)
memory_limit = 512M

; Maximum execution time of each script (increase for large uploads)
max_execution_time = 3600

; Maximum input time each script may spend parsing request data
max_input_time = 60
```

### Optional Settings for Better Performance
```ini
; Maximum number of files that can be uploaded simultaneously
max_file_uploads = 20

; Output buffering (can help with large file uploads)
output_buffering = 4096
```

## Web Server Configuration

### Apache (.htaccess)
If you cannot modify `php.ini`, you can try adding the following to your `.htaccess` file (note: on shared hosting, this may not override cPanel limits):

```apache
# Upload settings for large video files
php_value upload_max_filesize 128M
php_value post_max_size 128M
php_value memory_limit 512M
php_value max_execution_time 3600
php_value max_input_time 60
```

### Nginx
For Nginx, add to your server configuration:

```nginx
# Maximum allowed size for uploaded files
client_max_body_size 128M;

# Timeout settings for large uploads
client_body_timeout 60s;
client_header_timeout 60s;
```

## Laravel Configuration

The Laravel application has been updated to handle 100MB video uploads:

1. **Frontend (CMS)**: `VideoUploadModal.jsx` - Updated display text to show "max 100MB"
2. **Backend**: `MediaController.php` - Updated validation rule from `max:768000` (750MB) to `max:102400` (100MB)

## Verification

After making these changes, you can verify the configuration by:

1. Creating a PHP info file: `<?php phpinfo(); ?>`
2. Checking the following values:
   - `upload_max_filesize`
   - `post_max_size`
   - `memory_limit`
   - `max_execution_time`

## Important Notes

- Restart your web server after making `php.ini` changes
- The values shown are in kilobytes for Laravel validation (102400 KB = 100 MB)
- On shared hosting, you cannot exceed the limits set by your provider (cPanel)
- Test thoroughly with actual large video files before deploying to production

## File Size Calculations

- 100 MB = 102,400 KB (new limit)
- 128 MB = 131,072 KB (recommended php.ini setting for overhead)