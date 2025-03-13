<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->bigInteger('telegram_id')->unique();
            $table->string('role')->default(\App\Enums\UserRole::Unknown->value);
            $table->boolean('gender')->nullable();
            $table->date('birthday')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('telegram_id');
            $table->dropColumn('role');
            $table->dropColumn('gender');
            $table->dropColumn('birthday');
        });
    }
};
