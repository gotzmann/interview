<?php
declare(strict_types=1);

namespace App;

use App\Rule;

/**
 * Class RulesCollection
 *
 * Collect all pricing rules in one place
 *
 * @package App
 */
class RulesCollection
{
    private static array $rules = [];

    /**
     * Add pricing rule to the collection (or ignore if rule with such name already exists)
     * @param \App\Rule $rule
     */
    public static function add($rule)
    {
        $name = $rule::getName();
        if (!isset(static::$rules[$name])) {
            static::$rules[$name] = $rule;
        }
    }

    /**
     * Return all rules from collection
     */
    public static function all() : array
    {
        return static::$rules;
    }

}