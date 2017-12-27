<?php

namespace Payreto\Migrations;

use Payreto\Models\Database\Settings;
use Payreto\Services\Database\SettingsService;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

/**
* Migration to create Payreto configuration tables
*
* Class CreatePayretoTables
* @package Payreto\Migrations
*/
class CreatePayretoTables
{
	/**
	 * Run on plugin build
	 *
	 * Create Payreto configuration tables.
	 *
	 * @param Migrate $migrate
	 */
	public function run(Migrate $migrate)
	{
		/**
		 * Create the settings table
		 */
		try {
			$migrate->deleteTable(Settings::class);
		}
		catch (\Exception $e)
		{
			//Table does not exist
		}

		$migrate->createTable(Settings::class);

		// Set default payment method name in all supported languages.
		// $service = pluginApp(SettingsService::class);
		// $service->setInitialSettings();
	}
}
