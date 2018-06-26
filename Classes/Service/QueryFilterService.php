<?php

namespace Fab\Vidi\Service;

use Fab\Vidi\Tca\Tca;
use Fab\Vidi\Persistence\Query;
use TYPO3\CMS\Core\Utility\MathUtility;
use Fab\Vidi\Resolver\FieldPathResolver;
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
     * @throws \Fab\Vidi\Exception\InvalidKeyInArrayException
     * @throws \Fab\Vidi\Exception\NotExistingClassException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
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
     * @param $lat
     * @param $lng
     * @param int $radius
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException
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
     * @return array|\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface|\TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface
     * @throws \Fab\Vidi\Exception\InvalidKeyInArrayException
     * @throws \Fab\Vidi\Exception\NotExistingClassException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
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
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface|\TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface
     * @throws \Fab\Vidi\Exception\InvalidKeyInArrayException
     * @throws \Fab\Vidi\Exception\NotExistingClassException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
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
                $constraint = $query->equals(static::getFieldNameAndPath($query, $fieldName, $value), $value);
                break;
            case 'not_equal':
                $constraint = $query->logicalNot($query->equals(static::getFieldNameAndPath($query, $fieldName, $value), $value));
                break;
            case 'in':
                $constraint = $query->in(static::getFieldNameAndPath($query, $fieldName, $value), static::splitString($value));
                break;
            case 'not_in':
                $constraint = $query->logicalNot($query->in(static::getFieldNameAndPath($query, $fieldName, $value), static::splitString($value)));
                break;
            case 'begins_with':
                $constraint = $query->like(static::getFieldNameAndPath($query, $fieldName, $value), $value . '%');
                break;
            case 'not_begins_with':
                $constraint = $query->logicalNot($query->like(static::getFieldNameAndPath($query, $fieldName, $value), $value . '%'));
                break;
            case 'contains':
                $constraint = $query->like(static::getFieldNameAndPath($query, $fieldName, $value), '%' . $value . '%');
                break;
            case 'not_contains':
                $constraint = $query->logicalNot($query->like(static::getFieldNameAndPath($query, $fieldName, $value), '%' . $value . '%'));
                break;
            case 'ends_with':
                $constraint = $query->like(static::getFieldNameAndPath($query, $fieldName, $value), '%' . $value);
                break;
            case 'not_ends_with':
                $constraint = $query->logicalNot($query->like(static::getFieldNameAndPath($query, $fieldName, $value), '%' . $value));
                break;
            case 'is_empty':
                $constraint = $query->equals(static::getFieldNameAndPath($query, $fieldName, $value), '');
                break;
            //case 'is_null':
            //    $constraint = $query->logicalAnd($query->equals($fieldName, null));
            //    break;
            //case 'is_not_null':
            //    $constraint = $query->logicalAnd($query->equals($fieldName, null));
            //    break;
            case 'is_not_empty':
                $constraint = $query->logicalNot($query->equals(static::getFieldNameAndPath($query, $fieldName, $value), ''));
                break;
            case 'less':
                $constraint = $query->lessThan(static::getFieldNameAndPath($query, $fieldName, $value), $value);
                break;
            case 'less_or_equal':
                $constraint = $query->lessThanOrEqual(static::getFieldNameAndPath($query, $fieldName, $value), $value);
                break;
            case 'greater':
                $constraint = $query->greaterThan(static::getFieldNameAndPath($query, $fieldName, $value), $value);
                break;
            case 'greater_or_equal':
                $constraint = $query->greaterThanOrEqual(static::getFieldNameAndPath($query, $fieldName, $value), $value);
                break;
            case 'between':
                $constraint = $query->logicalAnd(
                    $query->greaterThanOrEqual(static::getFieldNameAndPath($query, $fieldName, $value), $valueAsArray[0]),
                    $query->lessThanOrEqual(static::getFieldNameAndPath($query, $fieldName, $value), $valueAsArray[1])
                );
                break;
            case 'not_between':
                $constraint = $query->logicalNot(
                    $query->greaterThanOrEqual(static::getFieldNameAndPath($query, $fieldName, $value), $valueAsArray[0]),
                    $query->lessThanOrEqual(static::getFieldNameAndPath($query, $fieldName, $value), $valueAsArray[1])
                );
                break;
            default: $constraint = $query->like(static::getFieldNameAndPath($query, $fieldName, $value), $value);
        }

        return $constraint;
    }

    /**
     * Resolves filed path for constraint
     *
     * @param Query $query
     * @param string $field
     * @param mixed $value
     * @return string
     * @throws \Fab\Vidi\Exception\InvalidKeyInArrayException
     * @throws \Fab\Vidi\Exception\NotExistingClassException
     */
    protected static function getFieldNameAndPath(Query $query, $field, $value)
    {
        $fieldPathResolver = GeneralUtility::makeInstance(FieldPathResolver::class);
        $fieldNameAndPath = $field;

        // Compute a few variables...
        // $dataType is generally equals to $this->dataType but not always... if fieldName is a path.
        $dataType = $fieldPathResolver->getDataType($field, $query->getType());
        $fieldName = $fieldPathResolver->stripFieldPath($field, $query->getType());
        $fieldPath = $fieldPathResolver->stripFieldName($field, $query->getType());

        if (Tca::table($dataType)->field($fieldName)->hasRelation()) {
            if (MathUtility::canBeInterpretedAsInteger($value)) {
                $fieldNameAndPath = $fieldName . '.uid';
            } else {
                $foreignTableName = Tca::table($dataType)->field($fieldName)->getForeignTable();
                $foreignTable = Tca::table($foreignTableName);
                $fieldNameAndPath = $fieldName . '.' . $foreignTable->getLabelField();
            }

            // If different means we should restore the prepended path segment for proper SQL parser.
            // This is true for a composite field, e.g items.sys_file_metadata for categories.
            if ($fieldName !== $fieldPath) {
                $fieldNameAndPath = $fieldPath . '.' . $fieldNameAndPath;
            }
        }

        return $fieldNameAndPath;
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