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
            $table->string('name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->unique();
            
            $table->boolean('is_confirmed')->default(0); //has verified email address
            $table->boolean('newsletter')->default(1);

            $table->string('image_url')->nullable(); //updates every 3 minutes for now

            $table->string('password');
            $table->char('token', 16);

            $table->string('stripe_id')->nullable();
            $table->string('stripe_last_four')->nullable();
            $table->string('stripe_brand')->nullable();            

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
