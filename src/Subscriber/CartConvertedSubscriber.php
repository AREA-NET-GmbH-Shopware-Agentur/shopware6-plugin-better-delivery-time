<?php declare(strict_types=1);

namespace AreanetBetterDeliveryTime\Subscriber;

use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartConvertedSubscriber implements EventSubscriberInterface
{


    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => 'onCartConverted',
        ];
    }

    public function onCartConverted(CartConvertedEvent $event): void
    {
        $cart               = $event->getCart();
        $cartConverted      = $event->getConvertedCart();

        $deliveryDates = [];
        foreach($cart->getDeliveries()->first()->getPositions() as $lineItemId => $deliveryPosition){
            $deliveryDates[$lineItemId] = $deliveryPosition->getDeliveryDate();
        }
        
        $cartConverted['customFields']['deliveryDates'] = $deliveryDates;
        $event->setConvertedCart($cartConverted);

    }
}
