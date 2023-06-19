<?php

namespace ProcessMaker\Helpers;

use stdClass;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ArrayHelper
{
    use FormatSecurityLogChanges;

    /**
     * This method parse and stdClass Object to an Associative Array
     * @param stdClass $parameter
     * @return array
     */
    public static function stdClassToArray(stdClass $parameter): array
    {
        $arrayConverted = json_decode(json_encode($parameter), true);

        return $arrayConverted;
    }

    /**
     * This method compares the keys of two arrays(new Values, old values) and adds (+) and (-) prefix
     * to the keys which are different between the arrays with "formatChanges" Trait function. 
     * The method returns an array with different keys values, and new values only with (+) prefix.
     * @param array $changedArray
     * @param array $originalArray
     * @return array
     */
    public static function getArrayDifferencesWithFormat(array $changedArray, array $originalArray): array
    {
        $arrayDiff = [];
        $displayChanges = array_diff_assoc($changedArray, $originalArray);
        $displayOriginal = array_diff_assoc($originalArray, $changedArray);

        $arrayHelper = new ArrayHelper();
        $arrayDiff = $arrayHelper->formatChanges($displayChanges, $displayOriginal);
        return $arrayDiff;
    }
}
