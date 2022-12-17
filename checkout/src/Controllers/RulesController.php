<?php
declare(strict_types=1);

namespace App\Controllers;

use Comet\Request;
use Comet\Response;

/**
 * Class RulesController
 * @package App\Controllers
 */
class RulesController
{
    public static function load(Request $request, Response $response)
    {
        // TODO Implement online loading for new rules

        return $response
            ->withStatus(200);
    }
}