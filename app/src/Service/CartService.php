<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartLine;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CartService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CartRepository $cartRepository,
        private ProductRepository $productRepository,
    ) {}

    public function add(int $productId, ?UserInterface $user, SessionInterface $session): void
    {
        if ($user instanceof User) {
            $cart = $this->getOrCreateDbCart($user);
            $line = $this->getOrCreateCartLine($cart, $productId);
            $line->setQuantity($line->getQuantity() + 1);
            $this->em->flush();
        } else {
            $cart = $session->get('cart', []);
            $cart[$productId] = ($cart[$productId] ?? 0) + 1;
            $session->set('cart', $cart);
        }
    }

    public function decrease(int $productId, ?UserInterface $user, SessionInterface $session): void
    {
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if (!$cart) {
                return;
            }
            foreach ($cart->getCartLines() as $line) {
                if ($line->getProduct()->getId() === $productId) {
                    $newQty = $line->getQuantity() - 1;
                    if ($newQty <= 0) {
                        $this->em->remove($line);
                    } else {
                        $line->setQuantity($newQty);
                    }
                    $this->em->flush();
                    break;
                }
            }
        } else {
            $cart = $session->get('cart', []);
            if (isset($cart[$productId])) {
                $cart[$productId]--;
                if ($cart[$productId] <= 0) {
                    unset($cart[$productId]);
                }
            }
            $session->set('cart', $cart);
        }
    }

    public function remove(int $productId, ?UserInterface $user, SessionInterface $session): void
    {
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if (!$cart) {
                return;
            }
            foreach ($cart->getCartLines() as $line) {
                if ($line->getProduct()->getId() === $productId) {
                    $this->em->remove($line);
                    $this->em->flush();
                    break;
                }
            }
        } else {
            $cart = $session->get('cart', []);
            unset($cart[$productId]);
            $session->set('cart', $cart);
        }
    }

    public function clear(?UserInterface $user, SessionInterface $session): void
    {
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if ($cart) {
                foreach ($cart->getCartLines() as $line) {
                    $this->em->remove($line);
                }
                $this->em->flush();
            }
        } else {
            $session->remove('cart');
        }
    }

    public function getCount(?UserInterface $user, SessionInterface $session): int
    {
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if (!$cart) {
                return 0;
            }
            $count = 0;
            foreach ($cart->getCartLines() as $line) {
                $count += $line->getQuantity();
            }
            return $count;
        }

        return array_sum($session->get('cart', []));
    }

    public function getCartItems(?UserInterface $user, SessionInterface $session): array
    {
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if (!$cart) {
                return [];
            }
            $items = [];
            foreach ($cart->getCartLines() as $line) {
                $items[] = [
                    'product'  => $line->getProduct(),
                    'quantity' => $line->getQuantity(),
                ];
            }
            return $items;
        }

        $sessionCart = $session->get('cart', []);
        if (empty($sessionCart)) {
            return [];
        }

        $products = $this->productRepository->findBy(['id' => array_keys($sessionCart)]);
        $items = [];
        foreach ($products as $product) {
            $items[] = [
                'product'  => $product,
                'quantity' => $sessionCart[$product->getId()],
            ];
        }
        return $items;
    }

    public function mergeSessionCartToDb(User $user, SessionInterface $session): void
    {
        $sessionCart = $session->get('cart', []);
        if (empty($sessionCart)) {
            return;
        }

        $cart = $this->getOrCreateDbCart($user);
        foreach ($sessionCart as $productId => $quantity) {
            $line = $this->getOrCreateCartLine($cart, $productId);
            $line->setQuantity($line->getQuantity() + $quantity);
        }

        $this->em->flush();
        $session->remove('cart');
    }

    private function getOrCreateDbCart(User $user): Cart
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $cart->setStatus('open');
            $this->em->persist($cart);
            $this->em->flush();
        }
        return $cart;
    }

    private function getOrCreateCartLine(Cart $cart, int $productId): CartLine
    {
        foreach ($cart->getCartLines() as $line) {
            if ($line->getProduct()->getId() === $productId) {
                return $line;
            }
        }

        $product = $this->productRepository->find($productId);
        $line = new CartLine();
        $line->setProduct($product);
        $line->setQuantity(0);
        $cart->addCartLine($line);
        $this->em->persist($line);

        return $line;
    }
}
