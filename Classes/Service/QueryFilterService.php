<?php

namespace Fab\Vidi\Service;

use Fab\Vidi\Persistence\Query;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class QueryFilterService
 * @package Fab\Vidi\Service
 */
class QueryFilterService
{
    /**
     * Sets query filters for query based on posted data
     *
     * @param Query $query
     * @param string $queryFilters
     */
    public static function applyFilters(Query &$query, string $queryFilters)
    {
        $queryFilters = ($queryFilters !== null) ? json_decode($queryFilters, true):[];

        $constraints = [];

        // inject constraints from query builder after all constraints provided
        // by Vidi are set and glue them together
        if (is_array($queryFilters) && array_key_exists('rules', $queryFilters) && !empty($queryFilters)) {
            $constraints[] = static::buildConstraintsForQueryRulesSingleLevel($query, $queryFilters);
        }

        if (isset($queryFilters['lat']) && $queryFilters['lat'] && isset($queryFilters['lng']) && $queryFilters['lng'] && isset($queryFilters['radius'])) {
            $constraints[] = static::getRadiusConstraintsFromLocation(
                $query,
                $queryFilters['lat'],
                $queryFilters['lng'],
                $queryFilters['radius']
            );
        }

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }
    }

    /**
     * Returns radius constraints from location constraints
     *
     * @param Query $query
     * @param float $lat
     * @param float $lng
     * @param int $radius
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface
     */
    public static function getRadiusConstraintsFromLocation(Query &$query, $lat, $lng, $radius = 5)
    {
        $lat = (float) $lat;
        $lng = (float) $lng;
        $r = 6371;  // earth's mean radius, km
        $kmPerDegreeOfLat = 111; // 69 miles

        $sqlSelectPart = "$r * ACOS(COS(RADIANS($lat)) * COS(RADIANS(`lat`)) * COS(RADIANS($lng) - RADIANS(`lng`)) + SIN(RADIANS($lat)) * SIN(RADIANS(`lat`))) AS `distance`";

        $query->addAdditionalStatementSelectPart($sqlSelectPart);
        $query->matching($query->lessThan('distance', $radius));

        return $query->logicalAnd([
            $query->greaterThanOrEqual('lat', $lat - ($radius / $kmPerDegreeOfLat)),
            $query->lessThanOrEqual('lat', $lat + ($radius / $kmPerDegreeOfLat)),
            $query->greaterThanOrEqual('lng', $lng - ($radius / ($kmPerDegreeOfLat * cos(deg2rad($lat))))),
            $query->lessThanOrEqual('lng', $lng + ($radius / ($kmPerDegreeOfLat * cos(deg2rad($lat)))))
        ]);
    }

    /**
     * Recursive build of query constraints
     *
     * It generates constraints that will be added to those defined by Vidi matcher.
     * This way we can have our custom constraints on top of the default one.
     *
     * @param Query $query
     * @param $rules
     * @return array
     */
    public static function buildConstraintsForQueryRulesSingleLevel(Query $query, $rules)
    {
        $constraints = [];

        if (array_key_exists('rules', $rules)) {
            foreach($rules['rules'] as $rule) {
                $constraints[] = static::buildConstraintsForQueryRulesSingleLevel($query, $rule);
            }
        } else {
            $constraints = static::getConstraintForRule($query, $rules);
            //$constraints = $query->$operatorName($rules['field'], $rules['value']);
        }

        if (array_key_exists('condition', $rules)) {
            $logicalOperatorName = 'logical' . ucfirst(strtolower($rules['condition']));
            return  $query->$logicalOperatorName($constraints);
        } else {
            return $constraints;
        }
    }

    /**
     * Returns constraint for a single rule
     *
     * @param Query $query
     * @param $rules
     * @return bool|\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface|\TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface
     */
    public static function getConstraintForRule(Query $query, $rules)
    {
        $fieldName = $rules['field'];
        // if we have checkbox (true/false) value is and array with single item
        // thus we have to use it but for between and not_between operators we
        // must use an array so we will store this array in separate variable
        $value = is_array($rules['value']) ? $rules['value'][0]:$rules['value'];
        $valueAsArray = $rules['value'];

        switch ($rules['operator']) {
            case 'equal':
                $constraint = $query->equals($fieldName, $value);
                break;
            case 'not_equal':
                $constraint = $query->logicalNot($query->equals($fieldName, $value));
                break;
            case 'in':
                $constraint = $query->in($fieldName, static::splitString($value));
                break;
            case 'not_in':
                $constraint = $query->logicalNot($query->in($fieldName, static::splitString($value)));
                break;
            case 'begins_with':
                $constraint = $query->like($fieldName, $value . '%');
                break;
            case 'not_begins_with':
                $constraint = $query->logicalNot($query->like($fieldName, $value . '%'));
                break;
            case 'contains':
                $constraint = $query->like($fieldName, '%' . $value . '%');
                break;
            case 'not_contains':
                $constraint = $query->logicalNot($query->like($fieldName, '%' . $value . '%'));
                break;
            case 'ends_with':
                $constraint = $query->like($fieldName, '%' . $value);
                break;
            case 'not_ends_with':
                $constraint = $query->logicalNot($query->like($fieldName, '%' . $value));
                break;
            case 'is_empty':
                $constraint = $query->equals($fieldName, '');
                break;
            //case 'is_null':
            //    $constraint = $query->logicalAnd($query->equals($fieldName, null));
            //    break;
            //case 'is_not_null':
            //    $constraint = $query->logicalAnd($query->equals($fieldName, null));
            //    break;
            case 'is_not_empty':
                $constraint = $query->logicalNot($query->equals($fieldName, ''));
                break;
            case 'less':
                $constraint = $query->lessThan($fieldName, $value);
                break;
            case 'less_or_equal':
                $constraint = $query->lessThanOrEqual($fieldName, $value);
                break;
            case 'greater':
                $constraint = $query->greaterThan($fieldName, $value);
                break;
            case 'greater_or_equal':
                $constraint = $query->greaterThanOrEqual($fieldName, $value);
                break;
            case 'between':
                $constraint = $query->logicalAnd(
                    $query->greaterThanOrEqual($fieldName, $valueAsArray[0]),
                    $query->lessThanOrEqual($fieldName, $valueAsArray[1])
                );
                break;
            case 'not_between':
                $constraint = $query->logicalNot(
                    $query->greaterThanOrEqual($fieldName, $valueAsArray[0]),
                    $query->lessThanOrEqual($fieldName, $valueAsArray[1])
                );
                break;
            default: $constraint = $query->like($fieldName, $value);
        }

        return $constraint;
    }

    /**
     * Split string using given pattern
     *
     * @param string $string
     * @param string $pattern
     * @return array
     */
    public static function splitString(string $string, string $pattern = '/[;, ]/')
    {
        $values = array_map('trim', preg_split($pattern, $string));

        if (!is_array($values)) {
            $values = [];
        }

        return $values;
    }
}