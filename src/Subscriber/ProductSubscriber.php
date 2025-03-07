<?php declare(strict_types=1);

namespace AreanetBetterDeliveryTime\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    protected SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService){
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $salesChannelContext    = $event->getSalesChannelContext();
        $page                   = $event->getPage();

        if($page->getProduct()->getDeliveryTime() || !$this->systemConfigService->get('AreanetBetterDeliveryTime.config.useDefaultDeliveryDate', $salesChannelContext->getSalesChannelId())){
            return;
        }

        $deliveryTime = $salesChannelContext->getShippingMethod()->getDeliveryTime();
        $page->getProduct()->setDeliveryTime($deliveryTime);

    }
}
