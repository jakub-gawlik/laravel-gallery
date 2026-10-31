<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table): void {
            $table->string('image_thumbnail_path')->nullable()->after('image_path');
            $table->unsignedInteger('image_width')->nullable()->after('image_thumbnail_path');
            $table->unsignedInteger('image_height')->nullable()->after('image_width');
            $table->unsignedInteger('image_thumbnail_width')->nullable()->after('image_height');
            $table->unsignedInteger('image_thumbnail_height')->nullable()->after('image_thumbnail_width');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table): void {
            $table->dropColumn([
                'image_thumbnail_path',
                'image_width',
                'image_height',
                'image_thumbnail_width',
                'image_thumbnail_height',
            ]);
        });
    }
};
