<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_meta', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('seoable');
            $table->string('route_key')->nullable();
            $table->string('locale')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['route_key', 'locale']);
        });

        Schema::create('seo_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('from')->unique();
            $table->string('to')->nullable();
            $table->unsignedSmallInteger('status')->default(301);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_404_log', function (Blueprint $table): void {
            $table->id();
            $table->string('url', 2048);
            $table->string('url_hash', 64)->unique();
            $table->string('referrer', 2048)->nullable();
            $table->unsignedBigInteger('hits')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
        Schema::dropIfExists('seo_404_log');
        Schema::dropIfExists('seo_redirects');
        Schema::dropIfExists('seo_meta');
    }
};