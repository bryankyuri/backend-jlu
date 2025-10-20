-- ============================================================================
-- ROLLBACK SQL SCRIPTS FOR PARALLEL STUDIO DATABASE
-- Generated: October 20, 2025
-- ============================================================================
-- WARNING: These scripts will DELETE data permanently!
-- Only use these if you need to rollback the table creation
-- ALWAYS backup your database before running rollback scripts
-- ============================================================================

-- ============================================================================
-- STEP 1: BACKUP EXISTING DATA (RECOMMENDED)
-- ============================================================================
-- Run these CREATE TABLE statements to backup your data before dropping
-- ============================================================================

/*
-- Backup contact_submissions table
CREATE TABLE IF NOT EXISTS contact_submissions_backup AS 
SELECT * FROM contact_submissions;

-- Backup showreels table
CREATE TABLE IF NOT EXISTS showreels_backup AS 
SELECT * FROM showreels;

-- Verify backups were created
SELECT COUNT(*) as contact_backup_count FROM contact_submissions_backup;
SELECT COUNT(*) as showreels_backup_count FROM showreels_backup;
*/


-- ============================================================================
-- STEP 2: DROP TABLES IN CORRECT ORDER (DUE TO FOREIGN KEYS)
-- ============================================================================
-- Must drop showreels first because it has foreign key to media table
-- ============================================================================

-- Drop showreels table (has foreign key constraint)
DROP TABLE IF EXISTS `showreels`;

-- Drop contact_submissions table (no foreign keys)
DROP TABLE IF EXISTS `contact_submissions`;


-- ============================================================================
-- STEP 3: VERIFY TABLES WERE DROPPED
-- ============================================================================

-- Check if tables still exist (should return empty result)
SELECT TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('contact_submissions', 'showreels');

-- If result is empty, tables were successfully dropped


-- ============================================================================
-- STEP 4: REMOVE FROM MIGRATIONS TABLE (OPTIONAL)
-- ============================================================================
-- If you want Laravel to forget these migrations were run
-- ============================================================================

/*
DELETE FROM migrations 
WHERE migration IN (
    '2025_10_09_035421_create_contact_submissions_table',
    '2025_10_10_103747_create_showreels_table',
    '2025_10_20_061507_make_poster_media_id_required_in_showreels_table',
    '2025_10_20_062123_remove_is_active_from_showreels_table'
);

-- Verify migrations were removed
SELECT * FROM migrations 
WHERE migration LIKE '%contact_submissions%' 
OR migration LIKE '%showreels%';
*/


-- ============================================================================
-- STEP 5: RESTORE FROM BACKUP (IF NEEDED)
-- ============================================================================
-- If you need to restore the backed up data
-- ============================================================================

/*
-- Restore contact_submissions
INSERT INTO contact_submissions 
SELECT * FROM contact_submissions_backup;

-- Restore showreels
INSERT INTO showreels 
SELECT * FROM showreels_backup;

-- Verify data was restored
SELECT COUNT(*) as contact_count FROM contact_submissions;
SELECT COUNT(*) as showreels_count FROM showreels;

-- Drop backup tables after successful restore
DROP TABLE IF EXISTS contact_submissions_backup;
DROP TABLE IF EXISTS showreels_backup;
*/


-- ============================================================================
-- NOTES
-- ============================================================================

/*
IMPORTANT WARNINGS:
- Dropping showreels will permanently delete all showreel data
- Dropping contact_submissions will permanently delete all form submissions
- Always backup before running DROP TABLE commands
- Foreign key constraints prevent accidental data loss in related tables

RECOMMENDED WORKFLOW:
1. Backup data using CREATE TABLE ... AS SELECT
2. Verify backups contain correct data
3. Drop tables in correct order (showreels first, then contact_submissions)
4. Verify tables no longer exist
5. If needed, restore from backup

ALTERNATIVE SOFT DELETE:
Instead of dropping tables, you could rename them:
RENAME TABLE contact_submissions TO contact_submissions_old;
RENAME TABLE showreels TO showreels_old;

This keeps data accessible for recovery if needed.
*/
