<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(private CartService $cartService) {}

    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add(int $id, SessionInterface $session, Request $request): Response
    {
        $this->cartService->add($id, $this->getUser(), $session);

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true, 'count' => $this->cartService->getCount($this->getUser(), $session)]);
        }

        return $this->redirectToRoute('app_product_show', ['id' => $id]);
    }

    #[Route('/cart/decrease/{id}', name: 'cart_decrease')]
    public function decrease(int $id, SessionInterface $session): JsonResponse
    {
        $this->cartService->decrease($id, $this->getUser(), $session);

        return $this->json(['success' => true, 'count' => $this->cartService->getCount($this->getUser(), $session)]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove(int $id, SessionInterface $session): JsonResponse
    {
        $this->cartService->remove($id, $this->getUser(), $session);

        return $this->json(['success' => true, 'count' => $this->cartService->getCount($this->getUser(), $session)]);
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(SessionInterface $session): JsonResponse
    {
        $this->cartService->clear($this->getUser(), $session);

        return $this->json(['success' => true, 'count' => 0]);
    }

    #[Route('/cart/offcanvas', name: 'cart_offcanvas')]
    public function offcanvas(SessionInterface $session): Response
    {
        return $this->render('cart/offcanvas.html.twig', [
            'cartItems' => $this->cartService->getCartItems($this->getUser(), $session),
        ]);
    }
}
