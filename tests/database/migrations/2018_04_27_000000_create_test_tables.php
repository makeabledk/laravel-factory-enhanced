<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->createTables('primary');
        $this->createTables('secondary');
    }

    protected function createTables($connection)
    {
        Schema::connection($connection)->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id')->nullable();
            $table->string('name');
            $table->text('tags')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->integer('satisfaction')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('departments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('manager_id')->nullable();
            $table->tinyInteger('active')->default(0);
            $table->tinyInteger('flagship')->default(0);
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection($connection)->create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('user_id');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('imageable');
            $table->timestamps();
        });

        Schema::connection($connection)->dropIfExists('users');
        Schema::connection($connection)->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
