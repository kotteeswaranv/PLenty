<?php

namespace Payreto\Methods;

use Plenty\Plugin\Log\Loggable;

/**
* Class PdrPaymentMethod
* @package Payreto\Methods
*/
class PdrPaymentMethod extends AbstractPaymentMethod
{
	use Loggable;

	/**
	 * @var name
	 */
	protected $name = 'Paydirect';

	/**
	 * @var logoFileName
	 */
	protected $logoFileName = 'pdr.png';

	/**
	 * @var settingsType
	 */
	protected $settingsType = 'paydirect';
}
