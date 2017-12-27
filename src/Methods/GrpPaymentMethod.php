<?php

namespace Payreto\Methods;

use Plenty\Plugin\Log\Loggable;

/**
* Class GrpPaymentMethod
* @package Payreto\Methods
*/
class GrpPaymentMethod extends AbstractPaymentMethod
{
	use Loggable;

	/**
	 * @var name
	 */
	protected $name = 'Giropay';

	/**
	 * @var logoFileName
	 */
	protected $logoFileName = 'grp.png';

	/**
	 * @var settingsType
	 */
	protected $settingsType = 'giropay';
}
