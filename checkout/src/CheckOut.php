<?php
declare(strict_types=1);

namespace App;

use App\Basket;
use App\RulesCollection;

/**
 * Class CheckOut
 *
 * The most important piece of the project. Please refer to example from docs:
 *
 * co = new CheckOut(pricing_rules);
 * co.scan(item);
 * co.scan(item);
 * price = co.total();
 *
 * @package App
 */
class CheckOut
{
    private Basket $basket;
    private static $rules;

    /**
     * CheckOut constructor.
     * Implemented according to the formal requirements
     * @param RulesCollection $rules Set of Pricing Rules
     */
    public function __construct($rules)
    {
        static::$rules = $rules;
        $this->basket = new Basket();
    }

    /**
     * Scan one good from the virtual shelf in order to compute total sum of order later
     * @param Item $item
     */
    public function scan(Item $item)
    {
        $this
            ->basket
            ->add($item);
    }

    /**
     * Return total amount of money for the order
     * @return float Money with fixed float point like $99.99
     */
    public function total() : float
    {
        // Apply all pricing rules for our basket
        foreach (static::$rules::all() as $rule) {
            $rule::apply($this->basket);
        }

        // Calculate basket total after all rules were applied
        $total = 0;
        foreach ($this->basket as $item => $count) {
            $total += $count * $item->getPrice();
        }

        return $total;
    }
}