<?php
declare(strict_types=1);

namespace App;

use App\Basket;
use App\Rules\PriceRule;
use App\Rules\PackRule;
use App\RulesCollection;
use PHPUnit\Framework\TestCase;

final class CheckOutTest extends TestCase
{
    /**
     * Test case for one A item in basket with base price of 50.0
     * @throws \Exception
     */
    public function testPriceRule(): void
    {
        $itemA = new Item('A');

        PriceRule::add('A', [ 'price' => 50.0 ]);
        RulesCollection::add(PriceRule::class);

        $checkout = new CheckOut(RulesCollection::class);
        $checkout->scan($itemA);

        $this->assertEquals(
            50.0,
            $checkout->total()
        );
    }

    /**
     * Test simple case of two items in basket
     * @throws \Exception
     */
    public function testTwoItemsInBusket(): void
    {
        $itemA = new Item('A');
        $itemB = new Item('B');

        PriceRule::add('A', [ 'price' => 50.0 ]);
        PriceRule::add('B', [ 'price' => 30.0 ]);
        RulesCollection::add(PriceRule::class);

        $checkout = new CheckOut(RulesCollection::class);
        $checkout->scan($itemA);
        $checkout->scan($itemB);

        $this->assertEquals(
            80.0,
            $checkout->total()
        );
    }

    /**
     * Test case with one discounted pack of 3 similar items
     * @throws \Exception
     */
    public function testPackOfThreeItems(): void
    {
        $itemA = new Item('A');

        PriceRule::add('A', [ 'price' => 50.0 ]);
        PackRule::add('A', [ 'pack' => 3, 'price' => 130.0 ]);
        RulesCollection::add(PriceRule::class);
        RulesCollection::add(PackRule::class);

        $checkout = new CheckOut(RulesCollection::class);
        $checkout->scan($itemA);
        $checkout->scan($itemA);
        $checkout->scan($itemA);

        $this->assertEquals(
            130.0,
            $checkout->total()
        );
    }

    /**
     * More complex case with one discounted pack of 3 similar items plus one extra item with normal price
     * @throws \Exception
     */
    public function testPackAndExtra(): void
    {
        $itemA = new Item('A');

        PriceRule::add('A', [ 'price' => 50.0 ]);
        PackRule::add('A', [ 'pack' => 3, 'price' => 130.0 ]);
        RulesCollection::add(PriceRule::class);
        RulesCollection::add(PackRule::class);

        $checkout = new CheckOut(RulesCollection::class);
        $checkout->scan($itemA);
        $checkout->scan($itemA);
        $checkout->scan($itemA);
        $checkout->scan($itemA);

        $this->assertEquals(
            130.0 + 50.0,
            $checkout->total()
        );
    }

}