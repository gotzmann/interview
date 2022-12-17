<?php
declare(strict_types=1);

namespace App;

use App\Item;

/**
 * Class Basket
 *
 * Stores all items for the current order
 *
 * @package App
 */
class Basket implements \Iterator
{
    /**
     * @var array $items Exemplars of each unique item stored in basket
     */
    private array $items = [];

    /**
     * @var array $counter Number of each particular SKU item in basket
     */
    private array $counters = [];

    /**
     * @var int $postition Iterator cursor position
     */
    private int $pos = 0;

    /**
     * Empty basket
     */
    public function empty()
    {
        $this->counters = [];
        $this->items = [];
    }

    /**
     * Add yet another item to the basket
     * @param Item $item One piece of something from our inventory
     */
    public function add(Item $item, int $count = 1)
    {
        $sku = $item->getSku();
        if (isset($this->counters[$sku])) {
            $this->counters[$sku] += $count;
        } else {
            $this->counters[$sku] = $count;
            $this->items[] = $item;
        }
    }

    /**
     * Delete all items of one kind from basket
     *
     * TODO Implement deleting part of items if there [count] number and it is not null
     *
     * @param Item $item One piece of something from our inventory
     * @param int $count How many items we should delete? Delete all by default
     */
    public function delete(Item $item, int $count = null)
    {
        $sku = $item->getSku();
        if (isset($this->counters[$sku])) {
            unset($this->counters[$sku]);
            foreach ($this->items as $num => $el) {
                if ($el->getSku() == $sku) {
                    unset($this->items[$num]);
                    break;
                }
            }

            // Rearrange array to remove index gaps like [1, 3, 4] -> [0, 1, 2]
            $this->items = array_values($this->items);
        }
    }

    // Implementations for methods of Iterator interface

    public function rewind() {
        $this->pos = 0;
    }

    public function current() {
        $sku = $this->items[$this->pos]->getSku();
        return $this->counters[$sku];
    }

    public function key() {
        return $this->items[$this->pos];
    }

    public function next() {
        $this->pos++;
    }

    public function valid() {
        return isset($this->items[$this->pos]);
    }
}