<?php

namespace Kf\KitBundle\Doctrine\ORM\Query;

use Doctrine\ORM\QueryBuilder;

class QueryBuilderUtils
{
    const _AND = 'AND';
    const _OR  = 'OR';

    public static function addLeftSearchToQuery(QueryBuilder $query, $fields, $search)
    {
        $x          = $query->expr();
        $multiField = is_array($fields);

        $w = $x->literal($search . '%');
        if ($multiField) {
            $rules = array();
            foreach ($fields as $field) {
                $rules[] = $x->like($field, $w);
            }
            $query->andWhere(self::mergeArrayOfRules($rules, self::_OR));
        } else {
            $query->andWhere($x->like($fields, $w));
        }
    }

    public static function addBasicSearchToQuery(QueryBuilder $query, $fields, $search)
    {
        $s          = explode(' ', $search);
        $x          = $query->expr();
        $multiField = is_array($fields);

        foreach ($s as $word) {
            $w = $x->literal('%' . $word . '%');
            if ($multiField) {
                $rules = array();
                foreach ($fields as $field) {
                    $rules[] = $x->like($field, $w);
                }
                $query->andWhere(self::mergeArrayOfRules($rules, self::_OR));
            } else {
                $query->andWhere($x->like($fields, $w));
            }
        }
    }

    public static function mergeArrayOfRules($rules, $mode = self::_AND)
    {
        return '(' . implode(' ' . $mode . ' ', $rules) . ')';
    }
}
