<?php

use Illuminate\Database\Schema\Blueprint;
use Vesp\Services\Migration;

class UserRole extends Migration
{

    public function up()
    {
        $this->schema->create('user_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->unique();
            $table->json('scope')->nullable();
            $table->timestamps();
        });
    }


    public function down()
    {
        $this->schema->drop('user_roles');
    }

}
