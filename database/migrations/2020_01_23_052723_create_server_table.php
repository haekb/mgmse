<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('address');
            $table->boolean('has_password')->default(false);
            $table->json('options')->nullable();
            // This will primarily be in redis, so we don't need to do typical db optimizations...yet.
            $table->string('game_name');
            $table->string('game_version');
            $table->string('status');
            /*
            $table->unsignedBigInteger('game_version_id');
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('status_id');
            */
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
        Schema::dropIfExists('servers');
    }
}
