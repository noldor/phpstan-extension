<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documented_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('keyless_documented_models', function (Blueprint $table): void {
            $table->string('name');
        });
        Schema::create('extended_documented_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('payload');
        });
    }
};
