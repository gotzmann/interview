<?php
declare(strict_types=1);

namespace App;

use App\Item;
use App\Basket;
use PHPUnit\Framework\TestCase;

final class BasketTest extends TestCase
{
    /**
     * Test addition of one item to basket
     * @throws \Exception
     */
    public function testOneItemInBasket(): void
    {
        $itemA = new Item('A');

        $basket = new Basket();
        $basket->add($itemA);

        $this->assertEquals(
            1,
            iterator_count($basket)
        );
    }

    /**
     * Test delete of one item to basket
     * @throws \Exception
     */
    public function testOneItemDelete(): void
    {
        $itemA = new Item('A');
        $itemB = new Item('B');

        $basket = new Basket();
        $basket->add($itemA);
        $basket->add($itemB);
        $basket->delete($itemA);

        $this->assertEquals(
            1,
            iterator_count($basket)
        );
    }

    /**
     * Test complex behavior
     * @throws \Exception
     */
    public function testFourInTwoOut(): void
    {
        $itemA = new Item('A');
        $itemB = new Item('B');
        $itemC = new Item('C');
        $itemD = new Item('D');

        $basket = new Basket();
        $basket->add($itemA);
        $basket->add($itemB);
        $basket->delete($itemA);
        $basket->add($itemC);
        $basket->add($itemD);
        $basket->delete($itemC);

        $expectedBasketContents = [ 'B', 'D' ];
        $actualBasketContents = [];

        foreach ($basket as $item => $count) {
            $actualBasketContents[] = $item->getSku();
        }

        $this->assertEquals(
            $expectedBasketContents,
            $actualBasketContents
        );
    }

}