<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Item;
use App\CheckOut;
use App\Rules\PackRule;
use App\Rules\PriceRule;
use App\RulesCollection;
use Comet\Request;
use Comet\Response;
use Illuminate\Database\Capsule\Manager as ORM;

/**
 * Class CheckoutController
 * @package App\Controllers
 */
class CheckoutController
{
    public function total(Request $request, Response $response)
    {
        // TODO Store rules within database or config file

        PriceRule::add('A', [ 'price' => 50.0 ]);
        PriceRule::add('B', [ 'price' => 30.0 ]);
        PriceRule::add('C', [ 'price' => 20.0 ]);
        PriceRule::add('D', [ 'price' => 15.0 ]);

        PackRule::add('A', [ 'pack' => 3, 'price' => 130.0 ]);
        PackRule::add('B', [ 'pack' => 2, 'price' => 45.0 ]);

        RulesCollection::add(PriceRule::class);
        RulesCollection::add(PackRule::class);

        $checkout = new CheckOut(RulesCollection::class);

        try {
            $basket= 0; // TODO Allow any basket in GET query
            $items = ORM::table('items')
                ->where('basket', '=', $basket)
                ->get();

        } catch(\Exception $e) {
            return $response
                ->withStatus(503);
        }

        foreach ($items as $item) {
            $item = new Item($item->sku);
            $checkout->scan($item);
        }

        $total = $checkout->total();

        return $response
            ->with([ 'total' => $total ]);
    }
}