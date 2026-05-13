<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PaymentController extends AbstractController
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly EntityManagerInterface $em,
        private readonly OrderRepository $orderRepository,
        private readonly CartService $cartService,
    ) {}

    #[IsGranted('ROLE_USER')]
    #[Route('/order/payment/success', name: 'payment_success', methods: ['GET'])]
    public function success(Request $request, SessionInterface $session): Response
    {
        $sessionId = $request->query->get('session_id');
        if (!$sessionId) {
            return $this->redirectToRoute('app_home');
        }

        $order = $this->orderRepository->findOneBy(['stripeSessionId' => $sessionId]);
        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($order->getStatus() === 'pending') {
            $stripeSession = $this->stripeService->retrieveSession($sessionId);

            if ($stripeSession->payment_status === 'paid') {
                $order->setStatus('paid');
                $this->em->flush();
                $this->cartService->clear($this->getUser(), $session);
            }
        }

        return $this->redirectToRoute('order_confirmation', ['id' => $order->getId()]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/order/payment/cancel/{id}', name: 'payment_cancel', methods: ['GET'])]
    public function cancel(int $id): Response
    {
        $order = $this->orderRepository->find($id);
        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->addFlash('warning', 'Le paiement a été annulé. Vous pouvez réessayer.');

        return $this->redirectToRoute('order_summary', ['id' => $id]);
    }

    /**
     * Webhook Stripe — pas de CSRF, signature Stripe validée.
     * Exposer ce endpoint dans le pare-feu Symfony en stateless/public.
     */
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request, LoggerInterface $logger): Response
    {
        $payload   = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature', '');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (\UnexpectedValueException $e) {
            $logger->warning('Stripe webhook: payload invalide — ' . $e->getMessage());
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $logger->warning('Stripe webhook: signature invalide — ' . $e->getMessage());
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleSessionCompleted($event->data->object),
            'checkout.session.expired'   => $this->handleSessionExpired($event->data->object),
            default                      => null,
        };

        return new Response('OK', Response::HTTP_OK);
    }

    private function handleSessionCompleted(\Stripe\Checkout\Session $session): void
    {
        $order = $this->findOrderBySession($session);
        if ($order && $order->getStatus() === 'pending') {
            $order->setStatus('paid');
            $this->em->flush();
        }
    }

    private function handleSessionExpired(\Stripe\Checkout\Session $session): void
    {
        $order = $this->findOrderBySession($session);
        if ($order && $order->getStatus() === 'pending') {
            $order->setStatus('payment_failed');
            $this->em->flush();
        }
    }

    private function findOrderBySession(\Stripe\Checkout\Session $session): ?\App\Entity\Order
    {
        $orderId = $session->metadata->order_id ?? null;
        if (!$orderId) {
            return null;
        }
        return $this->orderRepository->find((int) $orderId);
    }
}
