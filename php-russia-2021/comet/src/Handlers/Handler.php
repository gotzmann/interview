<?php
declare(strict_types=1);

namespace App\Handlers;

use Comet\Request;
use Comet\Response;
use Illuminate\Database\Capsule\Manager as ORM;

class Handler
{
    public static function listProducts(Request $request, Response $response)
    {
        $body = (string) $request->getBody();
        $params = json_decode($body);
        $brand = $params->brand ?? '';

        $products = ORM::select(
            "SELECT * FROM products p" .
            " JOIN brands b ON p.brand_id = b.brand_id " .
            " WHERE b.brand_name = ?",
            [ $brand ]
        );

        return $response
            ->with([ 'products' => $products ]);
    }
}

