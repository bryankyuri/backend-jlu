-- ============================================================================
-- MANUAL MIGRATION: Add Hero Banner Position Columns
-- Date: 2025-10-21
-- Description: Adds hero_banner_position_x and hero_banner_position_y columns
--              to the works table for dynamic background positioning
-- ============================================================================

-- STEP 1: Add the columns to the works table
-- This adds two new columns with default values after the hero_banner_image column
ALTER TABLE `works` 
ADD COLUMN `hero_banner_position_x` VARCHAR(50) NOT NULL DEFAULT 'center' AFTER `hero_banner_image`,
ADD COLUMN `hero_banner_position_y` VARCHAR(50) NOT NULL DEFAULT 'top' AFTER `hero_banner_position_x`;

-- ============================================================================
-- VERIFICATION QUERIES (Run these after the ALTER to verify)
-- ============================================================================

-- 1. Check if columns were added successfully
DESCRIBE `works`;

-- 2. Check default values were applied to existing records
SELECT id, title, hero_banner_image, hero_banner_position_x, hero_banner_position_y 
FROM `works` 
LIMIT 10;

-- 3. Count total records (all should have the default values)
SELECT 
    COUNT(*) as total_works,
    COUNT(hero_banner_position_x) as has_position_x,
    COUNT(hero_banner_position_y) as has_position_y
FROM `works`;

-- ============================================================================
-- ROLLBACK QUERY (Use this ONLY if you need to undo the changes)
-- ============================================================================

-- WARNING: This will permanently delete the position columns and their data
-- ALTER TABLE `works` 
-- DROP COLUMN `hero_banner_position_x`,
-- DROP COLUMN `hero_banner_position_y`;

-- ============================================================================
-- OPTIONAL: Sample queries to test updating position values
-- ============================================================================

-- Example 1: Update a specific work's position to top-left
-- UPDATE `works` 
-- SET hero_banner_position_x = 'left', hero_banner_position_y = 'top' 
-- WHERE id = 'YOUR_WORK_ID_HERE';

-- Example 2: Update a specific work's position to bottom-right
-- UPDATE `works` 
-- SET hero_banner_position_x = 'right', hero_banner_position_y = 'bottom' 
-- WHERE id = 'YOUR_WORK_ID_HERE';

-- Example 3: Update multiple works to center-center
-- UPDATE `works` 
-- SET hero_banner_position_x = 'center', hero_banner_position_y = 'center' 
-- WHERE id IN ('ID1', 'ID2', 'ID3');

-- ============================================================================
-- VALID VALUES REFERENCE
-- ============================================================================
-- hero_banner_position_x: 'left', 'center', 'right'
-- hero_banner_position_y: 'top', 'center', 'bottom'
-- ============================================================================
