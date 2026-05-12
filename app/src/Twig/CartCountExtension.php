<?php

namespace App\Twig;

use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartCountExtension extends AbstractExtension
{
    public function __construct(
        private CartService $cartService,
        private Security $security,
        private RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', $this->getCartCount(...)),
        ];
    }

    public function getCartCount(): int
    {
        $session = $this->requestStack->getSession();

        return $this->cartService->getCount($this->security->getUser(), $session);
    }
}
