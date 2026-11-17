<?php

declare(strict_types=1);

namespace FluxSE\PayumStripe\Action\StripeCheckoutSession;

use ArrayAccess;
use FluxSE\PayumStripe\Request\Api\Resource\AllSession;
use FluxSE\PayumStripe\Request\Api\Resource\ExpireSession;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Cancel;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class LegacyCancelAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param Cancel $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        // @link https://stripe.com/docs/api/payment_intents/cancel
        // > You cannot cancel the PaymentIntent for a Checkout Session.
        // > Expire the Checkout Session instead.
        $allSessionRequest = new AllSession(
            [
                'payment_intent' => $model['id'],
            ]
        );
        $this->gateway->execute($allSessionRequest);

        $sessions = $allSessionRequest->getApiResources();
        $session = $sessions->first();
        if (!$session instanceof Session) {
            return;
        }

        if ($session->status !== Session::STATUS_OPEN) {
            return;
        }

        $this->gateway->execute(new ExpireSession($session->id));
    }

    public function supports($request): bool
    {
        if (!$request instanceof Cancel) {
            return false;
        }

        $model = $request->getModel();
        if (!$model instanceof ArrayAccess) {
            return false;
        }

        if (!$model->offsetExists('object') || $model->offsetGet('object') !== PaymentIntent::OBJECT_NAME) {
            return false;
        }

        // if capture_method=automatic it means the payment intent was created from a checkout session without authorization
        return $model->offsetExists('capture_method') && in_array($model->offsetGet('capture_method'), [
            PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            PaymentIntent::CAPTURE_METHOD_AUTOMATIC_ASYNC,
        ], true);
    }
}
