<?php
declare(strict_types=1);

namespace App\Controllers;

use Comet\Request;
use Comet\Response;
use Comet\Validator;
use Illuminate\Database\Capsule\Manager as ORM;

/**
 * Class BasketController
 * @package App\Controllers
 */
class BasketController
{
    public static function add(Request $request, Response $response)
    {
        $payload = (string) $request->getBody();

        // Validation rules for JSON payload
        $rules = [
            'sku'    => 'required|alpha_dash',
            'basket' => 'integer',
            'count'  => 'integer',
        ];

        $messages = [
            'required'   => 'this field is required',
            'alpha_dash' => 'only alpha-numeric characters as well as dashes and underscores are allowed',
        ];

        // Return 400 Bad Request if there problems with validation
        $validator = new Validator;
        $validation = $validator->validate($payload, $rules, $messages);
        if (count($validation->getErrors())) {
            return $response
                ->with($validation->getErrors(), 400);
        }

        $data = json_decode($payload, true);

        // Store the item into persistent store (SQLite database)
        try {
            $basket= 0;
            $items = ORM::table('items')
                ->insert([
                    'sku'    => $data['sku'],
                    'basket' => isset($data['basket']) ? $data['basket'] : 0,
                    'count'  => isset($data['count']) ? $data['count'] : 1,
                ]);

        } catch(\Exception $e) {
            return $response
                ->withStatus(503);
        }

        return $response
            ->withStatus(200);
    }
}