<?php

namespace Tests\FluxSE\PayumStripe\Action\Api\WebhookEvent;

use FluxSE\PayumStripe\Action\Api\WebhookEvent\AbstractPaymentAction;
use FluxSE\PayumStripe\Action\Api\WebhookEvent\AbstractPaymentIntentAction;
use FluxSE\PayumStripe\Action\Api\WebhookEvent\AbstractWebhookEventAction;
use FluxSE\PayumStripe\Action\Api\WebhookEvent\PaymentIntentSucceededAction;
use FluxSE\PayumStripe\Request\Api\WebhookEvent\WebhookEvent;
use FluxSE\PayumStripe\Wrapper\EventWrapper;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\Token;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use PHPUnit\Framework\TestCase;
use Stripe\Event;
use Tests\FluxSE\PayumStripe\Action\GatewayAwareTestTrait;

final class PaymentIntentSucceededActionTest extends TestCase
{
    use GatewayAwareTestTrait;

    public function testShouldImplements(): void
    {
        $action = new PaymentIntentSucceededAction();

        $this->assertNotInstanceOf(ApiAwareInterface::class, $action);
        $this->assertInstanceOf(ActionInterface::class, $action);
        $this->assertInstanceOf(GatewayAwareInterface::class, $action);

        $this->assertInstanceOf(AbstractPaymentIntentAction::class, $action);
        $this->assertInstanceOf(AbstractPaymentAction::class, $action);
        $this->assertInstanceOf(AbstractWebhookEventAction::class, $action);
    }

    public function provideNotSupportedModels(): array
    {
        return [
            [[
                'id' => 'event_1',
                'data' => [
                    'object' => [],
                ],
                'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            ]],
            [[
                'id' => 'event_1',
                'data' => [
                    'object' => [
                        'metadata' => [
                            'token_hash' => 'test_hash',
                        ],
                    ],
                ],
                'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            ]],
            [[
                'id' => 'event_1',
                'data' => [
                    'object' => [
                        'capture_method' => 'manual',
                        'metadata' => [
                            'token_hash' => 'test_hash',
                        ],
                    ],
                ],
                'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            ]],
            [[
                'id' => 'event_1',
                'data' => [
                    'object' => [
                        'capture_method' => 'automatic_async',
                        'metadata' => [
                            'token_hash' => 'test_hash',
                        ],
                    ],
                ],
                'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            ]],
        ];
    }

    /** @dataProvider provideNotSupportedModels */
    public function testDoNotSupports(array $model): void
    {
        $action = new PaymentIntentSucceededAction();

        $event = Event::constructFrom($model);
        $eventWrapper = new EventWrapper('', $event);
        $webhookEvent = new WebhookEvent($eventWrapper);
        $supports = $action->supports($webhookEvent);
        $this->assertFalse($supports);
    }

    public function testSupports(): void
    {
        $action = new PaymentIntentSucceededAction();

        $model = [
            'id' => 'event_1',
            'data' => [
                'object' => [
                    'capture_method' => 'automatic',
                    'metadata' => [
                        'token_hash' => 'test_hash',
                    ],
                ],
            ],
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
        ];
        $event = Event::constructFrom($model);
        $eventWrapper = new EventWrapper('', $event);
        $webhookEvent = new WebhookEvent($eventWrapper);
        $supports = $action->supports($webhookEvent);
        $this->assertTrue($supports);
    }

    public function testShouldConsumeAWebhookEvent(): void
    {
        $model = [
            'id' => 'event_1',
            'data' => [
                'object' => [
                    'capture_method' => 'automatic',
                    'metadata' => [
                        'token_hash' => 'test_hash',
                    ],
                ],
            ],
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
        ];

        $event = Event::constructFrom($model);
        $token = new Token();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->isInstanceOf(GetToken::class)],
                [$this->isInstanceOf(Notify::class)]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (GetToken $request) use ($token) {
                    $this->assertEquals('test_hash', $request->getHash());
                    $request->setToken($token);
                }),
                $this->returnCallback(function (Notify $request) use ($token) {
                    $this->assertEquals($token, $request->getToken());
                })
            );

        $action = new PaymentIntentSucceededAction();
        $action->setGateway($gatewayMock);
        $eventWrapper = new EventWrapper('', $event);
        $webhookEvent = new WebhookEvent($eventWrapper);

        $supports = $action->supports($webhookEvent);
        $this->assertTrue($supports);

        $action->execute($webhookEvent);
    }
}
