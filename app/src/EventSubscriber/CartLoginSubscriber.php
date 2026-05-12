<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class CartLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CartService $cartService,
        private RequestStack $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $session = $this->requestStack->getSession();
        $this->cartService->mergeSessionCartToDb($user, $session);
    }
}
