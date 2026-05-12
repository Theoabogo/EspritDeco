<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Form\AddressType;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private EntityManagerInterface $em,
    ) {}

    #[Route('/order/address', name: 'order_address')]
    public function address(Request $request, SessionInterface $session): Response
    {
        $address = new Address();
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order = new Order();
            $order->setUser($this->getUser());
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setStatus('pending');
            $order->setAddress($address);

            $this->em->persist($order);
            $this->em->flush();

            $this->cartService->clear($this->getUser(), $session);

            return $this->redirectToRoute('order_confirmation', ['id' => $order->getId()]);
        }

        return $this->render('order/address.html.twig', [
            'form' => $form,
        ]);
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
