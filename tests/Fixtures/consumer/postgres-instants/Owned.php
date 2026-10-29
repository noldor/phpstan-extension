<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', static function (Blueprint $table): void {
            $table->timestamp('occurred_at', 6);
            $table->timestampsTz();
            $table->softDeletesTz(precision: 3);
            $table->timestampTz('accepted_at', 6);
            $table->timestampsTz(6);
            $table->softDeletesTz(precision: 6);
            $table->date('birthday');
            $table->bigInteger('unix_seconds');
        });
    }
};
