<?php
declare(strict_types=1);

namespace AreanetBetterDeliveryTime\Core\Checkout\Cart\Delivery;

use AreanetBetterDeliveryTime\Core\Checkout\Cart\Delivery\Struct\DeliveryDateExclusions;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryTime;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class DeliveryDateCalculator{

    protected SystemConfigService $systemConfigService;
    protected DeliveryDateExclusions $deliveryDateExclusions;

    public function __construct(SystemConfigService $systemConfigService){
        $this->systemConfigService = $systemConfigService;
    }

    public function createFromDeliveryTime(DeliveryTime $deliveryTime, SalesChannelContext $context): DeliveryDate
    {

        $excludeSaturday = $this->systemConfigService->get('AreanetBetterDeliveryTime.config.excludeSaturday', $context->getSalesChannelId());
        $excludeSunday = $this->systemConfigService->get('AreanetBetterDeliveryTime.config.excludeSunday', $context->getSalesChannelId());
        $this->deliveryDateExclusions = new DeliveryDateExclusions($excludeSaturday, $excludeSunday);

        return match ($deliveryTime->getUnit()) {
            DeliveryTimeEntity::DELIVERY_TIME_HOUR => $this->createDeliveryDate($deliveryTime, 'H', 'PT'),
            DeliveryTimeEntity::DELIVERY_TIME_DAY => $this->createDeliveryDate($deliveryTime, 'D'),
            DeliveryTimeEntity::DELIVERY_TIME_WEEK => $this->createDeliveryDate($deliveryTime, 'W'),
            DeliveryTimeEntity::DELIVERY_TIME_MONTH => $this->createDeliveryDate($deliveryTime, 'M'),
            DeliveryTimeEntity::DELIVERY_TIME_YEAR => $this->createDeliveryDate($deliveryTime, 'Y'),
            default => throw new \RuntimeException(sprintf('Not supported unit %s', $deliveryTime->getUnit())),
        };
    }

    protected function createDeliveryDate(DeliveryTime $deliveryTime,string  $interval, string $mode = 'P'){
        return new DeliveryDate(
            $this->create($mode . $deliveryTime->getMin() . $interval),
            $this->create($mode . $deliveryTime->getMax() . $interval)
        );
    }

    protected function create(string $interval): \DateTime
    {
        if($this->deliveryDateExclusions->hasNoExclusions()){
            return (new \DateTime())->add(new \DateInterval($interval));
        }

        $currentDateTime = new \DateTime();
        $nextDateTime    = clone $currentDateTime;
        $newDateTime     = (clone $currentDateTime)->add(new \DateInterval($interval));

        if(strpos($interval, 'D')) {
            $offset = 0;
            while ($nextDateTime <= $newDateTime) {
                if ($nextDateTime->format('N') == 6 && $this->deliveryDateExclusions->isExcludeSaturday()) {
                    $offset++;
                }
                if ($nextDateTime->format('N') == 7 && $this->deliveryDateExclusions->isExcludeSunday()) {
                    $offset++;
                }
                $nextDateTime->add(new \DateInterval('P1D'));
            }

            $newDateTime->add(new \DateInterval('P' . $offset . 'D'));
        }

        if($newDateTime->format('N') == 6 && $this->deliveryDateExclusions->isExcludeSaturday()){
            $newDateTime->add(new \DateInterval('P2D'));
        }elseif ($newDateTime->format('N') == 7 && $this->deliveryDateExclusions->isExcludeSunday()){
            $newDateTime->add(new \DateInterval('P1D' ));
        }

        return $newDateTime;
    }
}
