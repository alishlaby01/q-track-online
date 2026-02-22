<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * - arrived_at: لحظة وصول الفني للموقع (GPS) → العميل يرى "جاري العمل".
     * - users.phone: رقم تلفون الفني يظهر في صفحة التتبع عند "تم التعيين".
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->timestamp('arrived_at')->nullable()->after('check_in_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('arrived_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
