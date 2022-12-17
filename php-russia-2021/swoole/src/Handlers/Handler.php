<?php
declare(strict_types=1);

namespace App\Handlers;

use Swoole\HTTP\Request;
use Swoole\HTTP\Response;
use Illuminate\Database\Capsule\Manager as ORM;

class Handler
{
    public function listProducts(Request $request, Response $response)
    {
        $body = $request->getContent();
        $params = json_decode($body);
        $brand = $params->brand ?? '';

        $products = ORM::select(
            "SELECT * FROM products p" .
            " JOIN brands b ON p.brand_id = b.brand_id " .
            " WHERE b.brand_name = ?",
            [ $brand ]
        );

        $json = json_encode([ 'products' => $products ]);

        $response->header('Content-Type', 'application/json; charset=utf-8');

        // Используем end вместо write, чтобы исключить chunked transfer mode
        $response->end($json);
    }
}



