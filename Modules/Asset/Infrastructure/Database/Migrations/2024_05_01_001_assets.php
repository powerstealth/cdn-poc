<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * assets
         */
        Schema::create('assets', function (Blueprint $table) {
            $table->string('status',100)->index();
            $table->string('data.title',254)->index();
            $table->longText('data.description');
            $table->string('ingest.s3.key',254);
            $table->integer('ingest.file_length');
            $table->string('ingest.file_type',100);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
};
