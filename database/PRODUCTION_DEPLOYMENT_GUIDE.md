# Production Database Update Guide
## Parallel Studio - Add Contact Submissions & Showreels Tables

**Date:** October 20, 2025  
**Database:** MySQL/MariaDB  
**Tables:** `contact_submissions`, `showreels`

---

## 📋 Pre-Deployment Checklist

- [ ] Backup production database
- [ ] Verify `media` table exists (required for showreels foreign key)
- [ ] Test SQL scripts on staging environment first
- [ ] Have rollback plan ready
- [ ] Notify team of deployment window

---

## 🚀 Deployment Steps

### Step 1: Backup Database
```bash
# Create full database backup
mysqldump -u your_user -p your_database > backup_$(date +%Y%m%d_%H%M%S).sql

# Or use your hosting provider's backup tool
```

### Step 2: Connect to Production Database
```bash
mysql -u your_user -p your_database
```

### Step 3: Run Creation Scripts
```sql
-- Copy and paste from production_sql_scripts.sql
-- Or import the file:
SOURCE /path/to/production_sql_scripts.sql;
```

### Step 4: Verify Tables Created
```sql
-- Check tables exist
SHOW TABLES LIKE '%contact_submissions%';
SHOW TABLES LIKE '%showreels%';

-- Verify structure
DESCRIBE contact_submissions;
DESCRIBE showreels;
```

### Step 5: Test Application
- [ ] Test contact form submissions (career/pitch/produce)
- [ ] Test showreel creation in CMS
- [ ] Verify email notifications work
- [ ] Check API endpoints respond correctly

---

## 📊 Table Information

### Contact Submissions Table
**Purpose:** Store all contact form submissions from website  
**Records:** Career applications, project pitches, production inquiries  
**Features:**
- 3 form types with type-specific fields
- reCAPTCHA validation tracking
- Admin workflow (pending → reviewed → responded → archived)
- Automatic email notifications on submission

**Key Fields:**
- `form_type`: 'career', 'pitch', 'produce'
- `status`: 'pending', 'reviewed', 'responded', 'archived'
- `email`: User email (indexed for fast lookup)
- `created_at`: Submission timestamp (indexed)

### Showreels Table
**Purpose:** Store portfolio showreel videos  
**Dependencies:** Requires `media` table for poster images  
**Features:**
- Multiple video sources (upload, YouTube, Vimeo, Cloudflare)
- Required poster image (foreign key to media)
- Position-based ordering
- Always active (no is_active flag)

**Key Fields:**
- `video_url`: Primary video from media library
- `poster_media_id`: Required poster image (NOT NULL, CASCADE delete)
- `position`: Display order
- `video_source_type`: 'upload', 'youtube', or 'vimeo'

---

## 🔗 Foreign Key Constraints

### Showreels → Media
```
poster_media_id → media.id (ON DELETE CASCADE)
```
**Important:** If a media file used as poster is deleted, the showreel will also be deleted automatically.

---

## 📧 Email Templates

After tables are created, verify email templates exist:
- `resources/views/emails/contact-submission-career.blade.php`
- `resources/views/emails/contact-submission-pitch.blade.php`
- `resources/views/emails/contact-submission-produce.blade.php`

Each submission type sends a customized confirmation email.

---

## 🔄 Rollback Procedure

If you need to rollback:

```sql
-- 1. Backup data first (optional)
CREATE TABLE contact_submissions_backup AS SELECT * FROM contact_submissions;
CREATE TABLE showreels_backup AS SELECT * FROM showreels;

-- 2. Drop tables (showreels first due to foreign key)
DROP TABLE IF EXISTS showreels;
DROP TABLE IF EXISTS contact_submissions;

-- 3. Verify tables dropped
SHOW TABLES LIKE '%contact_submissions%';
SHOW TABLES LIKE '%showreels%';
```

See `production_rollback_scripts.sql` for complete rollback procedures.

---

## 🧪 Testing Queries

### Test Contact Submissions
```sql
-- Check submission counts by type
SELECT form_type, COUNT(*) as count 
FROM contact_submissions 
GROUP BY form_type;

-- Check submission counts by status
SELECT status, COUNT(*) as count 
FROM contact_submissions 
GROUP BY status;

-- View recent submissions
SELECT id, form_type, name, email, status, created_at 
FROM contact_submissions 
ORDER BY created_at DESC 
LIMIT 10;
```

### Test Showreels
```sql
-- Check total showreels
SELECT COUNT(*) as total FROM showreels;

-- View showreels with poster info
SELECT s.id, s.title, s.position, s.video_source_type, m.filename as poster
FROM showreels s
LEFT JOIN media m ON s.poster_media_id = m.id
ORDER BY s.position;

-- Check for missing poster images
SELECT id, title 
FROM showreels 
WHERE poster_media_id NOT IN (SELECT id FROM media);
```

---

## 📝 Post-Deployment Tasks

- [ ] Monitor error logs for any issues
- [ ] Test all 3 contact form types from frontend
- [ ] Create test showreel in CMS
- [ ] Verify email notifications are sent
- [ ] Check API responses are correct
- [ ] Update API documentation if needed
- [ ] Notify team deployment is complete

---

## 🆘 Troubleshooting

### Foreign Key Error on Showreels
**Error:** Cannot add foreign key constraint  
**Solution:** Verify `media` table exists and has `id` column
```sql
SHOW TABLES LIKE 'media';
DESCRIBE media;
```

### Cannot Insert Showreel (Poster Required)
**Error:** Column 'poster_media_id' cannot be null  
**Solution:** Ensure poster image is selected before saving showreel

### Contact Form Not Sending Emails
**Issue:** Email confirmation not received  
**Check:**
1. `.env` mail configuration
2. Queue worker running (if using queues)
3. Email templates exist in `resources/views/emails/`
4. Check Laravel logs: `storage/logs/laravel.log`

---

## 📞 Support Contacts

**Developer:** Bryan Kyuri  
**Repository:** bryankyuri/api-parallelstudio  
**Branch:** develop

---

## 📚 Related Documentation

- Laravel Migration Files:
  - `database/migrations/2025_10_09_035421_create_contact_submissions_table.php`
  - `database/migrations/2025_10_10_103747_create_showreels_table.php`
  - `database/migrations/2025_10_20_061507_make_poster_media_id_required_in_showreels_table.php`
  - `database/migrations/2025_10_20_062123_remove_is_active_from_showreels_table.php`

- API Controllers:
  - `app/Http/Controllers/ContactSubmissionController.php`
  - `app/Http/Controllers/ShowreelController.php`

- Models:
  - `app/Models/ContactSubmission.php`
  - `app/Models/Showreel.php`

- Email Mailers:
  - `app/Mail/ContactSubmissionConfirmation.php`
  - `app/Mail/ContactSubmissionNotification.php`

---

**Last Updated:** October 20, 2025  
**Version:** 1.0
