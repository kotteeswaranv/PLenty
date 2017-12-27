<?php

namespace Payreto\Methods;

use Plenty\Plugin\Log\Loggable;

/**
* Class AdbPaymentMethod
* @package Payreto\Methods
*/
class AdbPaymentMethod extends AbstractPaymentMethod
{
	use Loggable;

	/**
	 * @var name
	 */
	protected $name = 'Online Bank Transfer';

	/**
	 * @var logoFileName
	 */
	protected $logoFileName = 'adb.png';

	/**
	 * @var settingsType
	 */
	protected $settingsType = 'online-bank-transfer';
}
