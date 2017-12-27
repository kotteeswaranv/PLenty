<?php  
namespace Payreto\Migrations;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

use Payreto\Helper\PaymentHelper;
use Plenty\Plugin\Log\Loggable;

/**
* 
*/
class CreatePaymentMethod
{
	use Loggable;
	
	/**
	 * @var PaymentMethodRepositoryContract
	 */
	private $paymentMethodRepository;

	/**
	 * @var PaymentHelper
	 */
	private $paymentHelper;


	public function __construct(
		PaymentMethodRepositoryContract $paymentMethodRepository, 
		PaymentHelper $paymentHelper
	) {
		$this->paymentMethodRepository = $paymentMethodRepository;
		$this->paymentHelper = $paymentHelper;
	}

	public function run() {
		$this->createPaymentMethodByPaymentKey('PAYRETO_ACC', 'Credit Card Payment Methods');
		$this->createPaymentMethodByPaymentKey('PAYRETO_ECP', 'Easy Credit Payment Methods');
		$this->createPaymentMethodByPaymentKey('PAYRETO_DDS', 'Direct Debit SEPA');
		$this->createPaymentMethodByPaymentKey('PAYRETO_PPM', 'Paypal Payment Methods');
		$this->createPaymentMethodByPaymentKey('PAYRETO_ADB', 'Online Bank Transfer Payment Methods');
		$this->createPaymentMethodByPaymentKey('PAYRETO_PDR', 'Paydirect Payment Methods');
		$this->createPaymentMethodByPaymentKey('PAYRETO_GRP', 'Giropay Payment Methods');
	}

	/**
	 * Create payment method with given parameters if it doesn't exist
	 *
	 * @param string $paymentKey
	 * @param string $name
	 */
	private function createPaymentMethodByPaymentKey($paymentKey, $name)
	{
		// Check whether the ID of the Payreto payment method has been created
		$paymentMethod = $this->paymentHelper->getPaymentMethodByPaymentKey($paymentKey);
		if (is_null($paymentMethod))
		{
			$this->getLogger(__METHOD__)->error('Payreto:paymentMethod', $paymentMethod);
			$this->paymentMethodRepository->createPaymentMethod(
							[
								'pluginKey' => 'Payreto',
								'paymentKey' => (string) $paymentKey,
								'name' => $name
							]
			);
		}
	}
}

?>