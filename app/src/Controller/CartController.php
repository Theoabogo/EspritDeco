<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;


final class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add(int $id,  SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (!in_array($id, $cart)) {
            $cart[] = $id;
            $session->set('cart', $cart);
        } 

        return $this->redirectToRoute('app_product_show', ['id' => $id]);
    }

    #[Route('/cart/offcanavas', name: 'cart_offcanvas')]
    public function offcanvas(SessionInterface $session,
        ProductRepository $productRepository): Response
    {
       $cart = $session->get('cart', []);

$productIds = $cart;

$products = $productRepository->findBy([
    'id' => $productIds
    
]);

        return $this->render('cart/offcanvas.html.twig', [
            'products' => $products
        ]);
    }
}

