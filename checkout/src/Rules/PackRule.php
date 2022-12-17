<?php
declare(strict_types=1);

namespace App\Rules;

use App\Rule;
use App\Item;
use App\Basket;

/**
 * Class PackRule
 *
 * @package App
 */
abstract class PackRule extends Rule
{
    static protected string $name = 'pack';
    static protected array $skus = [];
    static protected array $params = [];

    /**
     * @param string $sku
     * @param array $params
     * @throws \Exception
     */
    public static function add(string $sku, array $params)
    {
        if (!array_key_exists('pack', $params) ||
            !array_key_exists('price', $params)
        ) {
            throw new \Exception("Please set pack size and price for $sku item!");
        }

        static::$skus[] = $sku;
        static::$params[$sku] = $params;
    }

    /**
     * Apply special pricing rules for packs of items
     *
     * We should transform N items of [SKU] into new one [SKU'Pack] with discounted price and
     * add all the extra items that do not form pack as N - (PackNumber * ItemsInPack) to basket again
     *
     * @param Basket $basket
     */
    public static function apply(Basket $basket)
    {
        // Fullscan basket at least one time, rescan in case of packs cause content of basket will change
        $firstScan = true;
        $needRescan = false;

        while(true) {

            if (!$firstScan && !$needRescan) {
                break;
            }

            $firstScan = false;
            $needRescan = false;

            // Iterate over all items and apply rules for packs of items if applicable
            foreach ($basket as $item => $count) {
                $sku = $item->getSku();

                // If there no rule for such SKU, continue
                if (!isset(static::$params[$sku])) {
                    continue;
                }

                $pack = static::$params[$sku]['pack'];
                $price = static::$params[$sku]['price'];

                // If there not enought items to form at least on pack, continue
                if ($count < $pack) {
                    continue;
                }

                // Reform basket: unite items to packs if possible
                $packsNumber = intval(floor($count / $pack));
                $extraItemsNumber = $count - $packsNumber * $pack;

                $itemPack = new Item($sku . '-pack');
                $itemPack->setPrice($price);

                $basket->delete($item);
                $basket->add($itemPack, $packsNumber);
                if ($extraItemsNumber) {
                    $basket->add($item, $extraItemsNumber);
                }

                $needRescan = true;
                break;
            }
        }
    }
}