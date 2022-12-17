<?php
declare(strict_types=1);

namespace App;

/**
 * Class Item
 *
 * Store one piece of any goods with SKU from our inventory
 *
 * @package App
 */
class Item
{
    private string $sku;
    private float $price = 0;

    /**
     * Item constructor
     * @param string $sku Unique SKU identificator
     */
    public function __construct(string $sku)
    {
        $this->sku = $sku;
    }

    /**
     * Simplify serializing with this
     * @return string SKU of item
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price)
    {
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    public function __toString() {
        return $this->sku;
    }
}