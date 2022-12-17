<?php
declare(strict_types=1);

namespace App\Rules;

use App\Rule;
use App\Item;
use App\Basket;

/**
 * Class PriceRule
 *
 * @package App
 */
abstract class PriceRule extends Rule
{
    static protected string $name = 'price';
    static protected array $skus = [];
    static protected array $params = [];

    /**
     * @param string $sku
     * @param array $params
     * @throws \Exception
     */
    public static function add(string $sku, array $params)
    {
        if (!array_key_exists('price', $params)) {
            throw new \Exception("Please set base price for $sku item!");
        }

        static::$skus[] = $sku;
        static::$params[$sku] = $params;
    }

    /**
     * @param Basket $basket
     * @throws \Exception
     */
    public static function apply(Basket $basket)
    {
        // Iterate over all items and apply all the appropriate pricing rules
        foreach ($basket as $item => $count) {
            $sku = $item->getSku();

            if (!isset(static::$params[$sku])) {
                throw new \Exception("Please set base price for $sku item!");
            }

            $item->setPrice(static::$params[$sku]['price']);
        }
    }

}