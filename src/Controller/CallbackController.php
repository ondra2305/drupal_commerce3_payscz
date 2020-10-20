<?php

namespace Drupal\commerce_payscz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Pages where Pays redirects user after using gateway or call it asynchronously.
 */
class CallbackController extends ControllerBase {

  /**
   * Page for successful payment.
   */
  public function okPage(Request $request) {
    if ($MerchantOrderNumber = $request->query->get('MerchantOrderNumber'))
      return $this->redirect(
        'commerce_payment.checkout.return',
        ['commerce_order' => $MerchantOrderNumber, 'step' => 'payment'],
        ['query' => $request->query->all()]
      );
    else
      return ['#markup' => t('Thank you!')];
  }

  /**
   * Page for failed payment.
   */
  public function errPage() {
  	$mail_url = 'mailto:' . \Drupal::config('system.site')->get('mail');
    $link = Link::fromTextAndUrl(t('Contact us'), Url::fromUri($mail_url))->toRenderable();
    return ['link' => $link];
  }

  /**
   * Page for asynchronous confirmation.
   */
  public function confirmPage(Request $request) {
    /** @var \Drupal\commerce_order\OrderStorage $order_storage */
    $order_storage = $this->entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($request->query->get('MerchantOrderNumber'));

    if (!$order) {
      return new Response('Order not found.', 404);
    }

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager()->getStorage('commerce_payment_gateway');
    // Load the payment gateways. This fires an event for filtering the
    // available gateways, and then evaluates conditions on all remaining ones.
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($order);

    // Try gateways until some will be successful.
    $last_exception = FALSE;
    foreach ($payment_gateways as $gateway) {
      try {
        $gateway->getPlugin()->onNotify($request);
        break;
      } catch (PaymentGatewayException $exeption) {
        $last_exception = $exeption;
      }
    }

    if ($last_exception) {
      throw $last_exception;
    }

    return new Response('', 200);
  }

}
