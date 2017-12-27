<?php  
namespace Payreto\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;
use Plenty\Plugin\Routing\ApiRouter;


class PayretoRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router, ApiRouter $apiRouter) {

		$apiRouter->version(
			['v1'],
			['namespace' => 'Payreto\Controllers', 'middleware' => 'oauth'],
			function ($apiRouter) {
				$apiRouter->post('payment/payreto/settings/', 'SettingsController@saveSettings');
				$apiRouter->get('payment/payreto/settings/{settingType}', 'SettingsController@loadSettings');
				$apiRouter->get('payment/payreto/setting/{plentyId}/{settingType}', 'SettingsController@loadSetting');
			}
		);

		// Routes for display General settings
		$router->get('payreto/settings/{settingType}','Payreto\Controllers\SettingsController@loadConfiguration');

		// Routes for 
		$router->post('payreto/settings/save','Payreto\Controllers\SettingsController@saveConfiguration');

		// Routes for Payreto payment widget
		$router->get('payment/payreto/pay/{id}', 'Payreto\Controllers\PaymentController@handlePayment');

		// Routes for Payreto status_url
		$router->get('payment/payreto/status', 'Payreto\Controllers\PaymentNotificationController@handleStatus');

		// Routes for Payreto payment return
		$router->get('payment/payreto/return/{id}/', 'Payreto\Controllers\PaymentController@handleReturn'); 
	}
}

?>