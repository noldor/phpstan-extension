<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('count');
            $table->jsonb('options');
            $table->string('database_label');
            $table->timestampTz('happened_at', 6);
        });
    }
};
