-- ============================================================================
-- PRODUCTION SQL SCRIPTS FOR PARALLEL STUDIO DATABASE
-- Generated: October 20, 2025
-- ============================================================================
-- Instructions:
-- 1. Backup your production database before running these scripts
-- 2. Run these scripts in order
-- 3. Verify the tables were created successfully
-- ============================================================================

-- ============================================================================
-- 1. CREATE CONTACT_SUBMISSIONS TABLE
-- ============================================================================
-- Purpose: Store all contact form submissions (Career, Pitch, Produce)
-- Dependencies: None
-- ============================================================================

CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_type` ENUM('career', 'pitch', 'produce') NOT NULL COMMENT 'Type of contact form submission',
  `name` VARCHAR(255) NOT NULL COMMENT 'Submitter full name',
  `email` VARCHAR(255) NOT NULL COMMENT 'Submitter email address',
  `message` TEXT NOT NULL COMMENT 'Main message content',
  
  -- Career form specific fields
  `subject` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Subject/Position for career applications',
  `portfolio_link` TEXT NULL DEFAULT NULL COMMENT 'Portfolio URL for career applications',
  
  -- Pitch form specific fields
  `document_link` TEXT NULL DEFAULT NULL COMMENT 'Supporting document link for pitch submissions',
  
  -- Produce form specific fields
  `company_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Company name for production inquiries',
  
  -- Security & tracking fields
  `recaptcha_token` TEXT NULL DEFAULT NULL COMMENT 'Google reCAPTCHA token',
  `recaptcha_score` DECIMAL(3, 2) NULL DEFAULT NULL COMMENT 'reCAPTCHA score (0.00 to 1.00)',
  `ip_address` VARCHAR(45) NULL DEFAULT NULL COMMENT 'Submitter IP address (IPv4 or IPv6)',
  `user_agent` TEXT NULL DEFAULT NULL COMMENT 'Browser user agent string',
  
  -- Admin management fields
  `status` ENUM('pending', 'reviewed', 'responded', 'archived') NOT NULL DEFAULT 'pending' COMMENT 'Current status of submission',
  `admin_notes` TEXT NULL DEFAULT NULL COMMENT 'Internal notes from admin',
  `responded_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When admin responded',
  `responded_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Admin user ID who responded',
  
  -- Timestamps
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  
  -- Indexes for performance
  INDEX `contact_submissions_form_type_index` (`form_type`),
  INDEX `contact_submissions_status_index` (`status`),
  INDEX `contact_submissions_created_at_index` (`created_at`),
  INDEX `contact_submissions_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contact form submissions from website';


-- ============================================================================
-- 2. CREATE SHOWREELS TABLE
-- ============================================================================
-- Purpose: Store showreel videos with poster images
-- Dependencies: Requires 'media' table to exist for foreign key
-- ============================================================================

CREATE TABLE IF NOT EXISTS `showreels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL COMMENT 'Showreel title',
  `description` TEXT NULL DEFAULT NULL COMMENT 'Showreel description',
  
  -- Video source fields
  `video_url` VARCHAR(255) NOT NULL COMMENT 'Primary video URL from media library',
  `video_source_type` VARCHAR(255) NOT NULL DEFAULT 'upload' COMMENT 'Video source: upload, youtube, or vimeo',
  `video_youtube_url` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Optional YouTube video URL',
  `video_vimeo_url` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Optional Vimeo video URL',
  `video_cloudflare_url` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Optional Cloudflare Stream URL',
  
  -- Poster image (REQUIRED - NOT NULL after migration)
  `poster_media_id` BIGINT UNSIGNED NOT NULL COMMENT 'Foreign key to media table for poster image',
  
  -- Display order
  `position` INT NOT NULL DEFAULT 1 COMMENT 'Display order position',
  
  -- Timestamps
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  
  -- Foreign key constraint (onDelete CASCADE - if media is deleted, showreel is deleted)
  CONSTRAINT `showreels_poster_media_id_foreign` 
    FOREIGN KEY (`poster_media_id`) 
    REFERENCES `media` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE RESTRICT,
  
  -- Index for performance (is_active removed in latest migration)
  INDEX `showreels_position_index` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Showreel videos for portfolio display';


-- ============================================================================
-- 3. VERIFY TABLES WERE CREATED
-- ============================================================================
-- Run these queries to verify the tables exist and have correct structure
-- ============================================================================

-- Check if contact_submissions table exists
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    TABLE_COMMENT
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'contact_submissions';

-- Check if showreels table exists
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    TABLE_COMMENT
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'showreels';

-- View contact_submissions table structure
DESCRIBE contact_submissions;

-- View showreels table structure
DESCRIBE showreels;

-- Check indexes on contact_submissions
SHOW INDEXES FROM contact_submissions;

-- Check indexes on showreels
SHOW INDEXES FROM showreels;


-- ============================================================================
-- 4. OPTIONAL: INSERT SAMPLE DATA FOR TESTING
-- ============================================================================
-- Uncomment and modify these if you want to test with sample data
-- ============================================================================

/*
-- Sample contact submission (Career)
INSERT INTO contact_submissions 
(form_type, name, email, message, subject, portfolio_link, status, created_at, updated_at)
VALUES
('career', 'John Doe', 'john.doe@example.com', 'I am interested in joining your team.', 
 'Senior Video Editor Position', 'https://portfolio.example.com', 'pending', NOW(), NOW());

-- Sample contact submission (Pitch)
INSERT INTO contact_submissions 
(form_type, name, email, message, document_link, status, created_at, updated_at)
VALUES
('pitch', 'Jane Smith', 'jane.smith@example.com', 'I have an exciting project idea to share.', 
 'https://docs.google.com/presentation/d/...', 'pending', NOW(), NOW());

-- Sample contact submission (Produce)
INSERT INTO contact_submissions 
(form_type, name, email, message, company_name, status, created_at, updated_at)
VALUES
('produce', 'Bob Johnson', 'bob@company.com', 'We need production services for our campaign.', 
 'ABC Corporation', 'pending', NOW(), NOW());
*/

/*
-- Sample showreel (requires existing media ID for poster)
-- Replace 1 with actual media ID from your media table
INSERT INTO showreels 
(title, description, video_url, video_source_type, poster_media_id, position, created_at, updated_at)
VALUES
('2024 Showreel', 'Our best work from 2024', '/storage/videos/showreel-2024.mp4', 
 'upload', 1, 1, NOW(), NOW());
*/


-- ============================================================================
-- 5. MIGRATIONS TRACKING (OPTIONAL)
-- ============================================================================
-- If you want Laravel to recognize these tables as migrated, insert into migrations table
-- ============================================================================

/*
INSERT INTO migrations (migration, batch) VALUES
('2025_10_09_035421_create_contact_submissions_table', 3),
('2025_10_10_103747_create_showreels_table', 8),
('2025_10_20_061507_make_poster_media_id_required_in_showreels_table', 9),
('2025_10_20_062123_remove_is_active_from_showreels_table', 10);
*/


-- ============================================================================
-- NOTES AND IMPORTANT INFORMATION
-- ============================================================================

/*
CONTACT_SUBMISSIONS TABLE:
- Stores 3 types of submissions: career, pitch, produce
- Each type has specific optional fields
- Includes reCAPTCHA validation tracking
- Status workflow: pending -> reviewed -> responded -> archived
- Email notifications sent automatically on submission

SHOWREELS TABLE:
- Primary video from media library (video_url)
- Optional additional video URLs (YouTube, Vimeo, Cloudflare)
- Poster image is REQUIRED (poster_media_id NOT NULL)
- No is_active flag - all showreels are always active
- Position field controls display order
- Cascade delete: if poster media is deleted, showreel is also deleted

DEPENDENCIES:
- showreels table requires 'media' table to exist
- Foreign key: poster_media_id -> media.id

NEXT STEPS:
1. Verify media table exists before creating showreels
2. Test foreign key constraints
3. Grant necessary permissions to application user
4. Update Laravel .env file with production credentials
5. Test API endpoints for both tables
*/
