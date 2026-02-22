<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إضافة حالة 'failed' لجدول الزيارات عند عدم تنفيذ المهمة
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('completed', 'incomplete', 'failed') NOT NULL DEFAULT 'incomplete'");
    }

    public function down(): void
    {
        DB::statement("UPDATE visits SET status = 'incomplete' WHERE status = 'failed'");
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('completed', 'incomplete') NOT NULL DEFAULT 'incomplete'");
    }
};
