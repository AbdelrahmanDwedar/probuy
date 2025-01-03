<?php

use App\Models\User;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'processing', 'completed', 'declined', 'canceled'])->default('pending');
            $table->foreignIdFor(User::class)->constrained('users')->cascadeOnDelete();
            $table->decimal('grand_total', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('currency')->default('EGP');
            $table->decimal('shipping_fee', 10, 2)->nullable();
            $table->string('shipping_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
