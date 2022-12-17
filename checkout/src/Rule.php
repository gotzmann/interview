<?php
declare(strict_types=1);

namespace App;

use App\Basket;

/**
 * Class Rule
 *
 * Pricing rule with some logic on given item or items collection
 * TODO Thinking about ways to implement it :)
 *
 * @package App
 */
abstract class Rule
{
    static protected string $name = '';
    static protected array $skus = [];
    static protected array $params = [];

    /**
     * @return string
     */
    public static function getName(): string
    {
        return static::$name;
    }

    /**
     * @param string $sku
     * @param array $params
     * @return mixed
     */
    public abstract static function add(string $sku, array $params);

    /**
     * @param \App\Basket $basket
     * @return mixed
     */
    public abstract static function apply(Basket $basket);

}