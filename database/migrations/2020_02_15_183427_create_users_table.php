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
            $table->string('name', 50)->nullable();
            $table->string('username', 15)->unique();
            $table->string('email')->unique();
            
            $table->boolean('is_confirmed')->default(0); //has verified email address
            $table->boolean('newsletter')->default(1);
            
            $table->boolean('do_not_disturb')->default(0);

            $table->string('status')->nullable();

            $table->unsignedBigInteger('file_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();

            $table->string('password');
            $table->char('token', 16);

            $table->string('stripe_id')->nullable();
            $table->string('stripe_last_four')->nullable();
            $table->string('stripe_brand')->nullable();            

            $table->timestamps();

            $table->index('file_id');
            $table->index('room_id');
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
