<?php declare(strict_types=1);

namespace AreanetBetterDeliveryTime\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    protected SystemConfigService $systemConfigService;
    protected EntityRepository $deliveryTimeRepository;

    public function __construct(SystemConfigService $systemConfigService, EntityRepository $deliveryTimeRepository){
        $this->systemConfigService      = $systemConfigService;
        $this->deliveryTimeRepository   = $deliveryTimeRepository;
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
        if(!$deliveryTime){
            $deliveryTimeId = $salesChannelContext->getShippingMethod()->getDeliveryTimeId();
            $deliveryTime = $this->deliveryTimeRepository->search(new Criteria([$deliveryTimeId]), $salesChannelContext->getContext())->first();
        }

        $page->getProduct()->setDeliveryTime($deliveryTime);

    }
}
