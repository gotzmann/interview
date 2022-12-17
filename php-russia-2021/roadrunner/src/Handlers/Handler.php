<?php
declare(strict_types=1);

namespace App\Handlers;

use Nyholm\Psr7\ServerRequest as Request;
use Nyholm\Psr7\Response;
use Illuminate\Database\Capsule\Manager as ORM;

class Handler
{
    public function listProducts(Request $request, Response $response)
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

        $json = json_encode([ 'products' => $products ]);

        $response
            ->getBody()
            ->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}

