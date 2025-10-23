-- ============================================================
-- MANUAL MIGRATION: Update work_gallery_items type column
-- ============================================================
-- Date: October 21, 2025
-- Purpose: Change type column from ENUM to VARCHAR(50) to support all 12 aspect ratio types
-- Tables affected: work_gallery_items
-- IMPORTANT: Backup your database before running this!
-- ============================================================

-- Step 1: Change ENUM to VARCHAR(50)
ALTER TABLE `work_gallery_items` 
MODIFY COLUMN `type` VARCHAR(50) NOT NULL;

-- Step 2: Update existing old type values to new format (if any exist)
-- This ensures backward compatibility for existing data

UPDATE `work_gallery_items` SET `type` = 'full-16:9' WHERE `type` = 'full-width';
UPDATE `work_gallery_items` SET `type` = '2col-16:9' WHERE `type` = '2col-full';
UPDATE `work_gallery_items` SET `type` = 'compare-16:9' WHERE `type` = 'compare-full';
-- Note: '2col-4:5' already has correct format, no update needed

-- ============================================================
-- VERIFICATION QUERIES (run these to check the changes)
-- ============================================================

-- Check the column type
DESCRIBE `work_gallery_items`;

-- Check all unique type values in the table
SELECT DISTINCT `type` FROM `work_gallery_items`;

-- Count records by type
SELECT `type`, COUNT(*) as count 
FROM `work_gallery_items` 
GROUP BY `type`;

-- ============================================================
-- ROLLBACK (if needed - run these to undo changes)
-- ============================================================

-- CAUTION: This will fail if you have new aspect ratio types already in use!
-- Only use this if you need to revert immediately after migration

-- ALTER TABLE `work_gallery_items` 
-- MODIFY COLUMN `type` ENUM('full-width', '2col-full', '2col-4:5', 'compare-full') NOT NULL;

-- UPDATE `work_gallery_items` SET `type` = 'full-width' WHERE `type` = 'full-16:9';
-- UPDATE `work_gallery_items` SET `type` = '2col-full' WHERE `type` = '2col-16:9';
-- UPDATE `work_gallery_items` SET `type` = 'compare-full' WHERE `type` = 'compare-16:9';

-- ============================================================
-- SUPPORTED TYPE VALUES (after migration)
-- ============================================================

-- Full Width (4 types):
--   full-16:9
--   full-2.35:1
--   full-2.39:1
--   full-4:5

-- Two Column (4 types):
--   2col-16:9
--   2col-2.35:1
--   2col-2.39:1
--   2col-4:5

-- Before/After Comparison (4 types):
--   compare-16:9
--   compare-2.35:1
--   compare-2.39:1
--   compare-4:5

-- Total: 12 gallery types

-- ============================================================
-- NOTES
-- ============================================================

-- 1. VARCHAR(50) is more than enough for all type values
-- 2. This migration is backward compatible - old values are converted
-- 3. The change allows future flexibility without additional migrations
-- 4. Make sure to update your API validation rules to match
-- 5. Frontend already uses these type values

-- ============================================================
