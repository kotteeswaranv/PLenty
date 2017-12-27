<?php
namespace Payreto\Procedures;

use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Plugin\Log\Loggable;

use Payreto\Services\PaymentService;
use Payreto\Helper\PaymentHelper;

/**
* Class RefundEventProcedure
* @package Payreto\Procedures
*/
class RefundEventProcedure
{
	use Loggable;

	/**
	 * @param EventProceduresTriggered $eventTriggered
	 * @param PaymentRepositoryContract $paymentRespository
	 * @param PaymentService $paymentService
	 * @param PaymentHelper $paymentHelper
	 * @throws \Exception
	 */
	public function run(
					EventProceduresTriggered $eventTriggered,
					PaymentRepositoryContract $paymentRespository,
					PaymentService $paymentService,
					PaymentHelper $paymentHelper
	) {
		/** @var Order $order */
		$order = $eventTriggered->getOrder();

		$this->getLogger(__METHOD__)->error('Payreto:order', $order);

		// only sales orders and credit notes are allowed order types to refund
		switch ($order->typeId)
		{
			case 1: // sales order
				$orderId = $order->id;
				break;
			case 4: // credit note
				$originOrders = $order->originOrders;
				if (! $originOrders->isEmpty() && $originOrders->count() > 0)
				{
					$originOrder = $originOrders->first();

					if ($originOrder instanceof Order)
					{
						if ($originOrder->typeId == 1)
						{
							$orderId = $originOrder->id;
						}
						else
						{
							$originOriginOrders = $originOrder->originOrders;
							if (is_array($originOriginOrders) && count($originOriginOrders) > 0)
							{
								$originOriginOrder = $originOriginOrders->first();
								if ($originOriginOrder instanceof Order)
								{
									$orderId = $originOriginOrder->id;
								}
							}
						}
					}
				}
				break;
		}

		if (empty($orderId))
		{
			$this->getLogger(__METHOD__)->error('Payreto:refundFailed', 'order not found');
			throw new \Exception('Refund Payreto payment failed! The given order is invalid!');
		}

		/** @var Payment[] $payment */
		$payments = $paymentRespository->getPaymentsByOrderId($orderId);

		$this->getLogger(__METHOD__)->error('Payreto:payments', $payments);

		if (count($payments) > 0)
		{
			/** @var Payment $payment */
			foreach ($payments as $payment)
			{
				if ($paymentHelper->isPayretoPaymentMopId($payment->mopId))
				{
					$transactionId = $paymentHelper->getPaymentPropertyValue(
									$payment->properties,
									PaymentProperty::TYPE_TRANSACTION_ID
					);

					$this->getLogger(__METHOD__)->error('Payreto:transactionId', $transactionId);

					if (isset($transactionId))
					{
						// refund the payment
						$refundResult = $paymentService->refund($transactionId, $payment);

						$this->getLogger(__METHOD__)->error('Payreto:refundResult', $refundResult);

						if ($refundResult['error'])
						{
							throw new \Exception('Refund Payreto payment failed!');
						}

						if ($refundResult['success'])
						{
							// create the new debit payment
							/** @var Payment $debitPayment */
							$this->getLogger(__METHOD__)->error('Payreto:payment', $payment);
							$debitPayment = $paymentHelper->createPlentyRefundPayment($payment, $refundResult['response']);

							$this->getLogger(__METHOD__)->error('Payreto:debitPayment', $debitPayment);

							if (isset($debitPayment) && $debitPayment instanceof Payment)
							{
								// assign the new debit payment to the order
								$this->getLogger(__METHOD__)->error('Payreto:debitPayment', $debitPayment);
								$paymentHelper->assignPlentyPaymentToPlentyOrder($debitPayment, (int)$order->id);
								$paymentHelper->updateRefundStatus(['statusId' => $debitPayment['status']], $orderId);
							}
						}
					}
				}
			}
		}
		else
		{
			throw new \Exception('Refund Payreto payment failed! The given order does not have payment!');
		}
	}
}
