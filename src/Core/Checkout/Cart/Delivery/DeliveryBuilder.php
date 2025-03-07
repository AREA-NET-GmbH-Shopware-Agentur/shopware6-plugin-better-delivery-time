<?php
declare(strict_types=1);

namespace AreanetBetterDeliveryTime\Core\Checkout\Cart\Delivery;

use AreanetBetterDeliveryTime\Core\Checkout\Cart\Delivery\Struct\DeliveryDateExclusions;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryBuilder as BaseDeliveryBuilder;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPosition;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryTime;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;


class DeliveryBuilder extends BaseDeliveryBuilder
{

    protected DeliveryDateCalculator $deliveryDateCalculator;
    protected SystemConfigService $systemConfigService;

    public function __construct(DeliveryDateCalculator $deliveryDateCalculator, SystemConfigService $systemConfigService){
        $this->deliveryDateCalculator = $deliveryDateCalculator;
        $this->systemConfigService = $systemConfigService;
    }

    public function buildByUsingShippingMethod(Cart $cart, ShippingMethodEntity $shippingMethod, SalesChannelContext $context): DeliveryCollection
    {
        $delivery = $this->buildSingleDelivery($shippingMethod, $cart->getLineItems(), $context);

        if (!$delivery) {
            return new DeliveryCollection();
        }

        return new DeliveryCollection([$delivery]);
    }

    private function buildSingleDelivery(
        ShippingMethodEntity $shippingMethod,
        LineItemCollection $collection,
        SalesChannelContext $context
    ): ?Delivery {
        $positions = new DeliveryPositionCollection();
        $deliveryTime = null;
        // use shipping method delivery time as default
        if ($shippingMethod->getDeliveryTime() !== null) {
            $deliveryTime = DeliveryTime::createFromEntity($shippingMethod->getDeliveryTime());
        }

        $this->buildPositions($collection, $positions, $deliveryTime, $context);

        if ($positions->count() <= 0) {
            return null;
        }

        return new Delivery(
            $positions,
            $this->getDeliveryDateByPositions($positions, $context),
            $shippingMethod,
            $context->getShippingLocation(),
            new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection())
        );
    }

    private function getDeliveryDateByPositions(DeliveryPositionCollection $positions, SalesChannelContext $context): DeliveryDate
    {
        // this function is only called if the provided collection contains a deliverable line item
        $max = $positions->first()->getDeliveryDate();

        foreach ($positions as $position) {
            $date = $position->getDeliveryDate();

            // detect the latest delivery date
            $earliest = $max->getEarliest() > $date->getEarliest() ? $max->getEarliest() : $date->getEarliest();

            $latest = $max->getLatest() > $date->getLatest() ? $max->getLatest() : $date->getLatest();

            if(!$this->systemConfigService->get('AreanetBetterDeliveryTime.config.disableBuffer', $context->getSalesChannelId())){
                if ($earliest->format('Y-m-d') === $latest->format('Y-m-d')) {
                    $latest = $latest->add(new \DateInterval('P1D'));
                }
            }

            $max = new DeliveryDate($earliest, $latest);
        }

        return $max;
    }

    private function buildPositions(
        LineItemCollection $items,
        DeliveryPositionCollection $positions,
        ?DeliveryTime $default,
        SalesChannelContext $context
    ): void {
        foreach ($items as $item) {
            if ($item->getDeliveryInformation() === null) {
                if ($item->getChildren()->count() > 0) {
                    $this->buildPositions($item->getChildren(), $positions, $default);
                }

                continue;
            }

            // each line item can override the delivery time
            $deliveryTime = $default;
            if ($item->getDeliveryInformation()->getDeliveryTime()) {
                $deliveryTime = $item->getDeliveryInformation()->getDeliveryTime();
            }

            if ($deliveryTime === null) {
                continue;
            }

            // create the estimated delivery date by detected delivery time
            $deliveryDate = $this->deliveryDateCalculator->createFromDeliveryTime($deliveryTime, $context);

            // create a restock date based on the detected delivery time
            $restockDate = $this->deliveryDateCalculator->createFromDeliveryTime($deliveryTime, $context);

            $restockTime = $item->getDeliveryInformation()->getRestockTime();

            // if the line item has a restock time, add this days to the restock date
            if ($restockTime) {
                $restockDate = $restockDate->add(new \DateInterval('P' . $restockTime . 'D'));
            }

            if ($item->getPrice() === null) {
                continue;
            }

            // if the item is completely in stock, use the delivery date
            if ($item->getDeliveryInformation()->getStock() >= $item->getQuantity()) {
                $position = new DeliveryPosition($item->getId(), clone $item, $item->getQuantity(), clone $item->getPrice(), $deliveryDate);
            } else {
                // otherwise use the restock date as delivery date
                $position = new DeliveryPosition($item->getId(), clone $item, $item->getQuantity(), clone $item->getPrice(), $restockDate);
            }

            $positions->add($position);
        }
    }
}

