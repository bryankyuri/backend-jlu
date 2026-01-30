<?php

// Run FAQ Groups Migration
// Execute from laravel-backend directory: php run_faq_migration.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Starting FAQ Groups Migration...\n\n";

try {
    // Step 1: Create faq_groups table
    echo "Step 1: Creating faq_groups table...\n";
    DB::statement("
        CREATE TABLE IF NOT EXISTS `faq_groups` (
          `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `category` enum('foreign','local') NOT NULL COMMENT 'Group category: foreign or local',
          `name` varchar(200) NOT NULL COMMENT 'Group name',
          `display_order` int(11) DEFAULT NULL COMMENT 'Display order (lower number = top)',
          `status` enum('active','inactive') NOT NULL DEFAULT 'active',
          `created_by` bigint(20) UNSIGNED DEFAULT NULL,
          `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `faq_groups_category_status_display_order_index` (`category`,`status`,`display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table created successfully\n\n";

    // Step 2: Update faqs table
    echo "Step 2: Updating faqs table...\n";
    
    // Check if column exists first
    $hasColumn = DB::select("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'faqs' 
        AND COLUMN_NAME = 'faq_group_id'
    ");
    
    if (empty($hasColumn)) {
        DB::statement("ALTER TABLE `faqs` ADD COLUMN `faq_group_id` bigint(20) UNSIGNED NULL AFTER `category`");
        DB::statement("ALTER TABLE `faqs` ADD KEY `faqs_faq_group_id_index` (`faq_group_id`)");
        DB::statement("
            ALTER TABLE `faqs` 
            ADD CONSTRAINT `faqs_faq_group_id_foreign` 
            FOREIGN KEY (`faq_group_id`) 
            REFERENCES `faq_groups` (`id`) 
            ON DELETE RESTRICT
        ");
        echo "✓ Column and foreign key added successfully\n\n";
    } else {
        echo "✓ Column already exists, skipping\n\n";
    }

    // Step 3: Seed Groups
    echo "Step 3: Seeding FAQ Groups...\n";
    
    $existingGroups = DB::table('faq_groups')->where('category', 'foreign')->count();
    
    if ($existingGroups == 0) {
        DB::table('faq_groups')->insert([
            ['category' => 'foreign', 'name' => 'About Parallel Studio', 'display_order' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'foreign', 'name' => 'Services & Capabilities', 'display_order' => 2, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'foreign', 'name' => 'File Delivery & Workflow', 'display_order' => 3, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'foreign', 'name' => 'Timeline & Revisions', 'display_order' => 4, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'foreign', 'name' => 'Collaboration & Communication', 'display_order' => 5, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'foreign', 'name' => 'Cost & Payment', 'display_order' => 6, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'foreign', 'name' => 'Security & Archiving', 'display_order' => 7, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'foreign', 'name' => 'Getting Started', 'display_order' => 8, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✓ 8 groups inserted successfully\n\n";
    } else {
        echo "✓ Groups already exist, skipping\n\n";
    }

    // Step 4: Seed FAQ Items
    echo "Step 4: Seeding FAQ Items...\n";
    
    $existingFaqs = DB::table('faqs')->where('category', 'foreign')->whereNotNull('faq_group_id')->count();
    
    if ($existingFaqs == 0) {
        $faqs = [
            // Group 1: About Parallel Studio
            ['category' => 'foreign', 'faq_group_id' => 1, 'question' => 'Where is Parallel Studio based?', 'answer' => '<p>We\'re based in Jakarta, Indonesia, specializing in color grading, VFX, and visual finishing for commercials, films, and series.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 1, 'question' => 'Do you work with international clients?', 'answer' => '<p>Yes. We regularly collaborate with agencies and production houses from outside Indonesia through remote workflow options. Our delivery pipeline is optimized for secure file transfers and fast turnaround times.</p>', 'status' => 'active', 'display_order' => 1],
            
            // Group 2: Services & Capabilities
            ['category' => 'foreign', 'faq_group_id' => 2, 'question' => 'What kind of projects do you handle?', 'answer' => '<p>We provide color grading, conforming, VFX finishing, and master delivery for TVCs, films, branded content, and documentaries.</p>', 'status' => 'active', 'display_order' => 3],
            ['category' => 'foreign', 'faq_group_id' => 2, 'question' => 'What software do you use?', 'answer' => '<p>Primarily DaVinci Resolve Studio for color and online, After Effects/Nuke for compositing, and Premiere/Flame for conform and finishing.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 2, 'question' => 'Can you handle HDR or OTT deliverables?', 'answer' => '<p>Absolutely. We master for Rec.709, HDR10, and Dolby Vision, and can prepare final assets according to regional broadcaster specs.</p>', 'status' => 'active', 'display_order' => 1],
            
            // Group 3: File Delivery & Workflow
            ['category' => 'foreign', 'faq_group_id' => 3, 'question' => 'How do we send you project materials?', 'answer' => '<p>You can send us XML/AAF and media via Dropbox, or Google Drive. We\'ll confirm specs and check the conform before grading starts.</p>', 'status' => 'active', 'display_order' => 4],
            ['category' => 'foreign', 'faq_group_id' => 3, 'question' => 'What delivery formats do you provide?', 'answer' => '<p>Typically ProRes 4444, DNxHR HQX, or H.264, depending on your needs. We also deliver multiple social and broadcast versions if required.</p>', 'status' => 'active', 'display_order' => 3],
            ['category' => 'foreign', 'faq_group_id' => 3, 'question' => 'Can we attend grading sessions remotely?', 'answer' => '<p>Yes. We support real-time remote grading via streaming (Louper.io, 8-bit SDR Rec.709) using our recommended devices.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 3, 'question' => 'What devices are recommended for the best Remote Grading experience?', 'answer' => '<p>To ensure accurate color reproduction and smooth review sessions, we recommend using a calibrated, high-quality display such as:</p><ul><li>iPhone X or newer (OLED models preferred)</li><li>iPad Pro M2 or newer with reference modes</li><li>MacBook Pro (2021 or newer) with Liquid Retina XDR display</li></ul><p>For professional-grade color accuracy, you may also connect to:</p><ul><li>Calibrated reference monitors that support wide color gamut / HDR</li></ul><p>For users not on Apple devices, please ensure your screen supports:</p><ul><li>100% rec.709 or wider color space</li><li>Factory or professionally calibrated display settings</li></ul><p>Additionally, we recommend:</p><ul><li>A fast and stable internet connection (minimum 30 Mbps down)</li><li>Viewing in a controlled lighting environment to maintain color consistency</li></ul>', 'status' => 'active', 'display_order' => 1],
            
            // Group 4: Timeline & Revisions
            ['category' => 'foreign', 'faq_group_id' => 4, 'question' => 'What\'s your typical turnaround time?', 'answer' => '<p><strong>TVCs:</strong> 1–2 days for color, 1 day for mastering.</p><p><strong>Short films or branded content:</strong> 2–4 days depending on duration.</p><p><strong>Feature films:</strong> scheduled per reel and complexity.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 4, 'question' => 'How do you handle revisions?', 'answer' => '<p>We include two rounds of revisions for each stage (color and online). Additional revisions can be arranged at a nominal cost.</p>', 'status' => 'active', 'display_order' => 1],
            
            // Group 5: Collaboration & Communication
            ['category' => 'foreign', 'faq_group_id' => 5, 'question' => 'What language do you use for communication?', 'answer' => '<p>We communicate comfortably in English and Bahasa Indonesia.</p>', 'status' => 'active', 'display_order' => 3],
            ['category' => 'foreign', 'faq_group_id' => 5, 'question' => 'What\'s your working schedule?', 'answer' => '<p>Monday to Friday, 10:00–19:00 (GMT+7). We can accommodate different time zones for regional clients upon request.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 5, 'question' => 'Can you work with our agency or production team directly?', 'answer' => '<p>Yes, we often collaborate with creative directors, DPs, and editors remotely — providing color previews and progress links at each stage.</p>', 'status' => 'active', 'display_order' => 1],
            
            // Group 6: Cost & Payment
            ['category' => 'foreign', 'faq_group_id' => 6, 'question' => 'How do you handle payment from outside Indonesia?', 'answer' => '<p>We accept international transfers via Wise or PayPal, or local invoices through partners in Singapore/Malaysia if needed.</p>', 'status' => 'active', 'display_order' => 3],
            ['category' => 'foreign', 'faq_group_id' => 6, 'question' => 'Do you charge in USD or local currency?', 'answer' => '<p>Most regional clients are billed in USD or SGD for convenience.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 6, 'question' => 'How do you estimate project costs?', 'answer' => '<p>Costs are based on duration, complexity, and number of deliverables. Send us your offline or reference and we\'ll provide a quick quote.</p>', 'status' => 'active', 'display_order' => 1],
            
            // Group 7: Security & Archiving
            ['category' => 'foreign', 'faq_group_id' => 7, 'question' => 'How do you ensure data security?', 'answer' => '<p>All projects are stored on encrypted RAID servers, with limited access.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 7, 'question' => 'Do you archive projects?', 'answer' => '<p>We keep backups for 6 months after delivery unless otherwise agreed. Long-term archiving can be arranged.</p>', 'status' => 'active', 'display_order' => 1],
            
            // Group 8: Getting Started
            ['category' => 'foreign', 'faq_group_id' => 8, 'question' => 'What\'s the best way to start a project?', 'answer' => '<p>Send us your offline reference, XML/AAF/EDL, and project brief via email or Frame.io. We\'ll review materials, confirm specs, and schedule your project slot.</p>', 'status' => 'active', 'display_order' => 2],
            ['category' => 'foreign', 'faq_group_id' => 8, 'question' => 'Can we schedule a test grade?', 'answer' => '<p>Yes, we often do a 1–2 shot test grade for new clients to ensure alignment in tone and look before the full session.</p>', 'status' => 'active', 'display_order' => 1],
        ];
        
        foreach ($faqs as $faq) {
            $faq['created_at'] = now();
            $faq['updated_at'] = now();
            DB::table('faqs')->insert($faq);
        }
        
        echo "✓ 23 FAQ items inserted successfully\n\n";
    } else {
        echo "✓ FAQs already exist, skipping\n\n";
    }

    echo "==========================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "==========================================\n\n";
    
    // Show summary
    $groupCount = DB::table('faq_groups')->count();
    $faqCount = DB::table('faqs')->whereNotNull('faq_group_id')->count();
    
    echo "Summary:\n";
    echo "- Total FAQ Groups: {$groupCount}\n";
    echo "- Total FAQs with groups: {$faqCount}\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
