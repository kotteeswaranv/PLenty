<?php

namespace Payreto\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Modules\Basket\Contracts\BasketItemRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;

use Payreto\Services\GatewayService;
use Payreto\Helper\PaymentHelper;
use Payreto\Services\PaymentService;
use Payreto\Services\OrderService;
/**
* Class PaymentController
* @package Payreto\Controllers
*/
class PaymentController extends Controller
{
	use Loggable;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Response
	 */
	private $response;

	/**
	 * @var BasketItemRepositoryContract
	 */
	private $basketItemRepository;

	/**
	 * @var SessionStorage
	 */
	private $sessionStorage;

	/**
	 *
	 * @var gatewayService
	 */
	private $gatewayService;

	/**
	 *
	 * @var paymentHelper
	 */
	private $paymentHelper;

	/**
	 *
	 * @var orderService
	 */
	private $orderService;

	/**
	 *
	 * @var paymentService
	 */
	private $paymentService;

	/**
	 * PaymentController constructor.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param BasketItemRepositoryContract $basketItemRepository
	 * @param SessionStorageService $sessionStorage
	 */
	public function __construct(
					Request $request,
					Response $response,
					BasketItemRepositoryContract $basketItemRepository,
					FrontendSessionStorageFactoryContract $sessionStorage,
					GatewayService $gatewayService,
					PaymentHelper $paymentHelper,
					OrderService $orderService,
					PaymentService $paymentService
	) {
		$this->request = $request;
		$this->response = $response;
		$this->basketItemRepository = $basketItemRepository;
		$this->sessionStorage = $sessionStorage;
		$this->gatewayService = $gatewayService;
		$this->paymentHelper = $paymentHelper;
		$this->orderService = $orderService;
		$this->paymentService = $paymentService;
	}

	/**
	 * handle return_url from payment gateway
	 */
	public function handleReturn($checkoutId)
	{
		$this->getLogger(__METHOD__)->error('Payreto:checkoutId', $checkoutId);
		$this->getLogger(__METHOD__)->error('Payreto:return_url', $this->request->all());
		$orderData = $this->orderService->placeOrder();

		$orderId = $orderData->order->id;

		$validation = $this->handleValidation($checkoutId, $orderId);

		$this->getLogger(__METHOD__)->error('Payreto:orderId', $orderId);

		$basketItems = $this->basketItemRepository->all();

		$this->getLogger(__METHOD__)->error('Payreto:basketItems', $basketItems);

		#Reset all basket.
		foreach ($basketItems as $basketItem)
		{
			$this->basketItemRepository->removeBasketItem($basketItem->id);
		}

		if ($validation) {
			return $this->response->redirectTo('execute-payment/'.$orderId);
		}
	}

	/**
	 * show payment widget
	 */
	public function handlePayment(Twig $twig, $checkoutId)
	{
		$paymentPageUrl = $this->paymentHelper->getDomain() . '/payment/payreto/return/' . $checkoutId . '/';
		$this->getLogger(__METHOD__)->error('Payreto:paymentPageUrl', $paymentPageUrl);

		$ccSettings = $this->paymentService->getPaymentSettings('credit-card');
		$cardType = '';

		if (is_array($ccSettings['cardType'])) {
			$cardType = implode(' ', $ccSettings['cardType']);
		} else {
			$cardType = $ccSettings['cardType'];
		}
		
		$data = [
			'cardType' => $cardType,
			'checkoutId' => $checkoutId,
			'paymentPageUrl' => $paymentPageUrl
		];

		return $twig->render('Payreto::Payment.PaymentWidget', $data);
	}

	/**
	 * handle validation payment
	 */
	public function handleValidation($checkoutId, $orderId)
	{
		$paymentData = [];

		$this->getLogger(__METHOD__)->error('Payreto:checkoutId', $checkoutId);
		
		$payretoSettings = $this->paymentService->getPayretoSettings();
		$ccSettings = $this->paymentService->getPaymentSettings('credit-card');

		$this->getLogger(__METHOD__)->error('Payreto:orderId', $orderId);

		$parameters = [
			'authentication.userId' => $payretoSettings['userId'],
			'authentication.password' => $payretoSettings['password'],
			'authentication.entityId' => $ccSettings['entityId']
		];

		$paymentConfirmation = $this->gatewayService->paymentConfirmation($checkoutId, $parameters);
		$paymentData['transaction_id'] = $paymentConfirmation['id'];
		$paymentData['paymentKey'] = 'PAYRETO_ACC';
		$paymentData['amount'] = $paymentConfirmation['amount'];
		$paymentData['currency'] = $paymentConfirmation['currency'];
		$paymentData['status'] = 2;
		$paymentData['orderId'] = $orderId;
		$this->sessionStorage->getPlugin()->setValue('PayretoTransactionId', $paymentConfirmation['id']);

		$this->getLogger(__METHOD__)->error('Payreto:paymentConfirmation', $paymentConfirmation);

		if ($paymentConfirmation['result']['code'] == '000.100.110') {
			$this->paymentHelper->updatePlentyPayment($paymentData);
			return true;
		}
		else 
		{
			return false;
		}
	}

}
