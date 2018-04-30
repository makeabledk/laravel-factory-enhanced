<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTestTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('divisions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('manager_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('simples', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });
    }
}
