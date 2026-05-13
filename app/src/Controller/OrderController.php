<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Form\AddressType;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private StripeService $stripeService,
        private LoggerInterface $logger,
    ) {}

    #[Route('/order/address', name: 'order_address')]
    public function address(Request $request): Response
    {
        $order = $this->orderRepository->findPendingOrderByUser($this->getUser());

        if ($order === null) {
            $order = new Order();
            $order->setUser($this->getUser());
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setStatus('pending');
        }

        $address = $order->getAddress() ?? new Address();
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setAddress($address);

            $this->em->persist($order);
            $this->em->flush();

            return $this->redirectToRoute('order_summary', ['id' => $order->getId()]);
        }

        return $this->render('order/address.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/order/summary/{id}', name: 'order_summary')]
    public function summary(Order $order, SessionInterface $session): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $items = $this->cartService->getCartItems($this->getUser(), $session);

        $total = array_reduce($items, static function (float $carry, array $item): float {
            return $carry + $item['product']->getPrice() * $item['quantity'];
        }, 0.0);

        return $this->render('order/summary.html.twig', [
            'order' => $order,
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/order/confirm/{id}', name: 'order_confirm', methods: ['POST'])]
    public function confirm(Order $order, Request $request, SessionInterface $session): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('order_confirm_' . $order->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $items = $this->cartService->getCartItems($this->getUser(), $session);
        if (empty($items)) {
            $this->addFlash('danger', 'Votre panier est vide.');
            return $this->redirectToRoute('order_summary', ['id' => $order->getId()]);
        }

        $successUrl = $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL)
            . '?session_id={CHECKOUT_SESSION_ID}';

        $cancelUrl = $this->generateUrl(
            'payment_cancel',
            ['id' => $order->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $stripeSession = $this->stripeService->createCheckoutSession($order, $items, $successUrl, $cancelUrl);

        $this->logger->info('Stripe session créée', [
            'session_id'  => $stripeSession->id,
            'session_url' => $stripeSession->url,
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

        if (!$stripeSession->url) {
            $this->logger->error('Stripe session URL est null', ['session_id' => $stripeSession->id]);
            $this->addFlash('danger', 'Erreur lors de la création de la session de paiement.');
            return $this->redirectToRoute('order_summary', ['id' => $order->getId()]);
        }

        $order->setStripeSessionId($stripeSession->id);
        $this->em->flush();

        return $this->redirect($stripeSession->url);
    }

    #[Route('/order/confirmation/{id}', name: 'order_confirmation')]
    public function confirmation(Order $order): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/confirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
