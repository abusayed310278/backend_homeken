<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('re_accounts')->onDelete('cascade');
            $table->boolean('read_message')->default(true);
            $table->boolean('search_engine')->default(false);
            $table->boolean('home_city')->default(true);
            $table->boolean('trip_type')->default(true);
            $table->boolean('length_stay')->default(true);
            $table->boolean('booked_services')->default(true);
            $table->boolean('ai_features')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privacy_settings');
    }
};
