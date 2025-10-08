-- SQL queries to add video URL columns to works table in production
-- Run these queries in phpMyAdmin or your MySQL client

-- Add video_vimeo_url column
ALTER TABLE `works` ADD COLUMN `video_vimeo_url` VARCHAR(255) NULL AFTER `video_project_poster`;

-- Add video_youtube_url column  
ALTER TABLE `works` ADD COLUMN `video_youtube_url` VARCHAR(255) NULL AFTER `video_vimeo_url`;

-- Add video_cloudflare_url column
ALTER TABLE `works` ADD COLUMN `video_cloudflare_url` VARCHAR(255) NULL AFTER `video_youtube_url`;

-- Verify the columns were added successfully
DESCRIBE `works`;

-- Optional: Check if the columns exist before adding them (safer approach)
-- You can run these one by one to check if columns already exist:

-- Check if video_vimeo_url column exists
SELECT COUNT(*) as column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'parallel_studio' 
  AND TABLE_NAME = 'works' 
  AND COLUMN_NAME = 'video_vimeo_url';

-- Check if video_youtube_url column exists  
SELECT COUNT(*) as column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'parallel_studio' 
  AND TABLE_NAME = 'works' 
  AND COLUMN_NAME = 'video_youtube_url';

-- Check if video_cloudflare_url column exists
SELECT COUNT(*) as column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'parallel_studio' 
  AND TABLE_NAME = 'works' 
  AND COLUMN_NAME = 'video_cloudflare_url';

-- Safe version with IF NOT EXISTS logic (MySQL 8.0+)
-- Use this if you want to be extra safe and avoid duplicate column errors:

/*
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'parallel_studio' 
                 AND TABLE_NAME = 'works' 
                 AND COLUMN_NAME = 'video_vimeo_url') = 0,
              'ALTER TABLE `works` ADD COLUMN `video_vimeo_url` VARCHAR(255) NULL AFTER `video_project_poster`',
              'SELECT "video_vimeo_url column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'parallel_studio' 
                 AND TABLE_NAME = 'works' 
                 AND COLUMN_NAME = 'video_youtube_url') = 0,
              'ALTER TABLE `works` ADD COLUMN `video_youtube_url` VARCHAR(255) NULL AFTER `video_vimeo_url`',
              'SELECT "video_youtube_url column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'parallel_studio' 
                 AND TABLE_NAME = 'works' 
                 AND COLUMN_NAME = 'video_cloudflare_url') = 0,
              'ALTER TABLE `works` ADD COLUMN `video_cloudflare_url` VARCHAR(255) NULL AFTER `video_youtube_url`',
              'SELECT "video_cloudflare_url column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
*/