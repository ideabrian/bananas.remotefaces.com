<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('is_confirmed')->default(0);
            $table->boolean('newsletter')->default(1);
            $table->string('password');
            $table->char('token', 16);

            $table->string('stripe_id')->nullable();
            $table->string('stripe_last_four')->nullable();
            $table->string('stripe_brand')->nullable();

            $table->boolean('is_listener')->default(0);
            $table->boolean('is_available_to_listen')->default(0);

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
