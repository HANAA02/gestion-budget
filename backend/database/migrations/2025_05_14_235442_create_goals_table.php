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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('titre', 100);
            $table->text('description')->nullable();
            $table->decimal('montant_cible', 10, 2);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut', 50)->default('en cours');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};