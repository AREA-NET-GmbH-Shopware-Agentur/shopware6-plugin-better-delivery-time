<?php
namespace AreanetBetterDeliveryTime\Core\Checkout\Cart\Delivery\Struct;

use Shopware\Core\Framework\Struct\Struct;

class DeliveryDateExclusions extends Struct
{
    /**
     * @var boolean
     */
    protected $excludeSaturday = false;

    /**
     * @var boolean
     */
    protected $excludeSunday = false;

    public function __construct($excludeSaturday = false, $excludeSunday = false)
    {
        $this->excludeSaturday = $excludeSaturday;
        $this->excludeSunday = $excludeSunday;
    }

    /**
     * @return bool
     */
    public function isExcludeSaturday(): bool
    {
        return $this->excludeSaturday;
    }

    /**
     * @return bool
     */
    public function isExcludeSunday(): mixed
    {
        return $this->excludeSunday;
    }
    /**
     * @return bool
     */
    public function hasNoExclusions(): bool
    {
        return !$this->excludeSaturday && !$this->excludeSunday;
    }
}
