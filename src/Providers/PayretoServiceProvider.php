<?php
 
namespace Payreto\Providers;
 
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendUpdateInvoiceAddress;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Log\Loggable;

use Payreto\Helper\PaymentHelper;
use Payreto\Services\PaymentService;
use Payreto\Methods\AccPaymentMethod;
use Payreto\Methods\EcpPaymentMethod;
use Payreto\Methods\DdsPaymentMethod;
use Payreto\Methods\PpmPaymentMethod;
use Payreto\Methods\AdbPaymentMethod;
use Payreto\Methods\PdrPaymentMethod;
use Payreto\Methods\GrpPaymentMethod;
 
class PayretoServiceProvider extends ServiceProvider
{
	use Loggable;


    public function register()
    {
 		$this->getApplication()->register(PayretoRouteServiceProvider::class);
    }

    public function boot(
    	Dispatcher $eventDispatcher,
		PaymentHelper $paymentHelper,
		PaymentService $paymentService,
		BasketRepositoryContract $basket,
		PaymentMethodContainer $payContainer,
		PaymentMethodRepositoryContract $paymentMethodService,
		EventProceduresService $eventProceduresService
    ) {
    	$this->registerPaymentMethod($payContainer, 'PAYRETO_ACC', AccPaymentMethod::class);
    	$this->registerPaymentMethod($payContainer, 'PAYRETO_ECP', EcpPaymentMethod::class);
    	$this->registerPaymentMethod($payContainer, 'PAYRETO_DDS', DdsPaymentMethod::class);
    	$this->registerPaymentMethod($payContainer, 'PAYRETO_PPM', PpmPaymentMethod::class);
    	$this->registerPaymentMethod($payContainer, 'PAYRETO_ADB', AdbPaymentMethod::class);
    	$this->registerPaymentMethod($payContainer, 'PAYRETO_PDR', PdrPaymentMethod::class);
    	$this->registerPaymentMethod($payContainer, 'PAYRETO_GRP', GrpPaymentMethod::class);

    	// Register Payreto Refund Event Procedure
		$eventProceduresService->registerProcedure(
						'Payreto',
						ProcedureEntry::PROCEDURE_GROUP_ORDER,
						[
						'de' => 'Rückzahlung der Payreto-Zahlung',
						'en' => 'Refund the Payreto-Payment'
						],
						'Payreto\Procedures\RefundEventProcedure@run'
		);

		// Register Payreto Update Order Status Event Procedure
		$eventProceduresService->registerProcedure(
						'Payreto',
						ProcedureEntry::PROCEDURE_GROUP_ORDER,
						[
						'de' => 'Update order status the Payreto-Payment',
						'en' => 'Update order status the Payreto-Payment'
						],
						'Payreto\Procedures\UpdateOrderStatusEventProcedure@run'
		);

    	// Listen for the event that gets the payment method content
		$eventDispatcher->listen(
						GetPaymentMethodContent::class,
						function (GetPaymentMethodContent $event) use ($paymentHelper, $basket, $paymentService, $paymentMethodService) {
							if ($paymentHelper->isPayretoPaymentMopId($event->getMop()))
							{
								$content = $paymentService->getPaymentContent(
												$basket->load(),
												$paymentMethodService->findByPaymentMethodId($event->getMop())
								);
								$this->getLogger(__METHOD__)->error('Payreto:Content', $content);

								$event->setValue(isset($content['content']) ? $content['content'] : null);
								$event->setType(isset($content['type']) ? $content['type'] : '');
							}
						}
		);

		// Listen for the event that executes the payment
		$eventDispatcher->listen(
						ExecutePayment::class,
						function (ExecutePayment $event) use ($paymentHelper, $paymentService) {
							if ($paymentHelper->isPayretoPaymentMopId($event->getMop()))
							{
								$result = $paymentService->executePayment($event->getOrderId());

								$event->setType($result['type']);
								$event->setValue($result['value']);
							}
						}
		);
    }

    /**
	 * register payment method.
	 *
	 * @param PaymentMethodContainer $payContainer
	 * @param string $paymentKey
	 * @param PaymentMethodService $class
	 */
	private function registerPaymentMethod($payContainer, $paymentKey, $class)
	{
		$payContainer->register(
			'Payreto::' . $paymentKey,
			$class,
			[
				AfterBasketChanged::class,
				AfterBasketItemAdd::class,
				AfterBasketCreate::class,
				FrontendLanguageChanged::class,
				FrontendUpdateInvoiceAddress::class
			]
		);
	}
}