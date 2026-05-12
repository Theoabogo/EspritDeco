<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;


final class CartController extends AbstractController
{
    // Structure du panier en session : [productId => quantité]

    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add(int $id, SessionInterface $session, ProductRepository $productRepository, Request $request): Response
    {
        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true, 'count' => array_sum($cart)]);
        }

        return $this->redirectToRoute('app_product_show', ['id' => $id]);
    }

    #[Route('/cart/decrease/{id}', name: 'cart_decrease')]
    public function decrease(int $id, SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$id])) {
            $cart[$id]--;
            if ($cart[$id] <= 0) {
                unset($cart[$id]);
            }
        }

        $session->set('cart', $cart);

        return $this->json(['success' => true, 'count' => array_sum($cart)]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove(int $id, SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);
        unset($cart[$id]);
        $session->set('cart', $cart);

        return $this->json(['success' => true, 'count' => array_sum($cart)]);
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(SessionInterface $session): JsonResponse
    {
        $session->remove('cart');

        return $this->json(['success' => true, 'count' => 0]);
    }

    #[Route('/cart/offcanvas', name: 'cart_offcanvas')]
    public function offcanvas(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $cartItems = [];

        if (!empty($cart)) {
            $products = $productRepository->findBy(['id' => array_keys($cart)]);
            foreach ($products as $product) {
                $cartItems[] = [
                    'product'  => $product,
                    'quantity' => $cart[$product->getId()],
                ];
            }
        }

        return $this->render('cart/offcanvas.html.twig', [
            'cartItems' => $cartItems,
        ]);
    }
}
