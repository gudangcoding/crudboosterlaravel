<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCmsModulesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cms_modules', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name', 50)->nullable();
			$table->string('icon', 50)->nullable();
			$table->string('path', 50)->nullable();
			$table->string('table_name', 50)->nullable();
			$table->string('controller', 50)->nullable();
			$table->boolean('is_protected')->default(0);
			$table->boolean('is_active')->default(0);
			$table->timestamps();
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
		Schema::drop('cms_modules');
	}

}
