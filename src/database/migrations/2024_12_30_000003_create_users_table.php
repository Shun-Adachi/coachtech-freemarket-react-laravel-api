<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('current_post_code')->nullable();
            $table->string('current_address', 255)->nullable();
            $table->string('current_building', 255)->nullable();
            $table->string('shipping_post_code')->nullable();
            $table->string('shipping_address', 255)->nullable();
            $table->string('shipping_building', 255)->nullable();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('login_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
