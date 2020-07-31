<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentation', function (Blueprint $table) {
            $table->bigIncrements('id_documentation');
            $table->string('name')->nullable(false);
            $table->date('document_date')->nullable(false);
            $table->string('study_name')->nullable(false);
            $table->string('version')->nullable(false);
            $table->integer('investigator')->default(0)->nullable(false);
            $table->integer('controller')->default(0)->nullable(false);
            $table->integer('monitor')->default(0)->nullable(false);
            $table->integer('reviewer')->default(0)->nullable(false);
            $table->integer('deleted')->default(0)->nullable(false);
            $table->timestamps();
            //EO convention nom clé multiple?
            $table->foreign('study_name')->references('name')->on('studies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documentation');
    }
}
