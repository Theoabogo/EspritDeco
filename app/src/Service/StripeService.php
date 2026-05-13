<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    private StripeClient $stripe;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret,
    ) {
        $this->stripe = new StripeClient([
            'api_key'        => $secretKey,
            'stripe_version' => '2024-06-20',
        ]);
    }

    public function createCheckoutSession(
        Order $order,
        array $items,
        string $successUrl,
        string $cancelUrl,
    ): Session {
        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round($item['product']->getPrice() * 100),
                    'product_data' => [
                        'name' => $item['product']->getTitle(),
                    ],
                ],
                'quantity' => $item['quantity'],
            ];
        }

        return $this->stripe->checkout->sessions->create([
            'line_items'     => $lineItems,
            'mode'           => 'payment',
            'success_url'    => $successUrl,
            'cancel_url'     => $cancelUrl,
            'customer_email' => $order->getUser()->getEmail(),
            'metadata'       => [
                'order_id' => $order->getId(),
            ],
        ]);
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }

    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, $this->webhookSecret);
    }
}
