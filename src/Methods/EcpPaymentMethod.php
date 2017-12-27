<?php

namespace Payreto\Methods;

use Plenty\Plugin\Log\Loggable;

/**
* Class EcpPaymentMethod
* @package Payreto\Methods
*/
class EcpPaymentMethod extends AbstractPaymentMethod
{
	use Loggable;

	/**
	 * @var name
	 */
	protected $name = 'Easy Credit';

	/**
	 * @var logoFileName
	 */
	protected $logoFileName = 'aec.png';

	/**
	 * @var settingsType
	 */
	protected $settingsType = 'easy-credit';
}
