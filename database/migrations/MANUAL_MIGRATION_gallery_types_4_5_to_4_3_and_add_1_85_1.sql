-- Manual Migration SQL for Shared Hosting
-- Migration: Update gallery types from 4:5 to 4:3 and add support for 1.85:1 ratio
-- Date: 2025-10-22
-- 
-- IMPORTANT: Run this SQL script on your shared hosting database
-- This will update all existing gallery items that use 4:5 ratio to 4:3
--
-- Backup your database before running this migration!

-- Update all gallery items from 4:5 to 4:3
UPDATE `work_gallery_items` 
SET `type` = REPLACE(`type`, '-4:5', '-4:3')
WHERE `type` LIKE '%-4:5';

-- Verification query (optional - run this to check the results)
-- SELECT `type`, COUNT(*) as count 
-- FROM `work_gallery_items` 
-- GROUP BY `type`;

-- Note: The new 1.85:1 ratio types are now available for use:
-- - full-1.85:1 (Full Width 1.85:1)
-- - 2col-1.85:1 (Two Column 1.85:1)  
-- - compare-1.85:1 (Before/After Comparison 1.85:1)
--
-- The database column 'type' is VARCHAR(50), so no schema changes are needed.
-- You can start using the new ratio types immediately after this migration.

-- ROLLBACK (if needed - updates 4:3 back to 4:5)
-- UPDATE `work_gallery_items` 
-- SET `type` = REPLACE(`type`, '-4:3', '-4:5')
-- WHERE `type` LIKE '%-4:3';
