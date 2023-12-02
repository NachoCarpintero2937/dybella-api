<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id'); // Clave foránea para el usuario
            $table->foreign('user_id')->references('id')->on('users'); // Establecer relación con la tabla "users"
            $table->unsignedBigInteger('price_id'); // Clave foránea para el usuario
            $table->foreign('price_id')->references('id')->on('prices'); // Establecer relación con la tabla "users"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
