<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTalksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('talks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('daily_room_name');
            $table->unsignedBigInteger('caller_id')->nullable();
            $table->unsignedBigInteger('listener_id')->nullable();
            $table->boolean('is_answered')->default(0);
            $table->boolean('is_initialized')->default(0);
            $table->timestamps();

            $table->index('caller_id');
            $table->index('listener_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('talks');
    }
}
