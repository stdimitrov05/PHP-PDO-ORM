<?php

namespace StdimitrovOrm\Tools;

use Exception;

readonly class Helper
{
    /**
     * Converts a string to camelCase.
     *
     * @param string $string The input string to convert.
     * @return string The converted camelCase string.
     */
    protected static function toCamelCase(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    /**
     * Converts a string to snake_case.
     *
     * @param string $string The input string to convert.
     * @return string The converted snake_case string.
     */
    public static function toSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $string));
    }

    /**
     * Converts a string to PascalCase.
     *
     * @param string $string The input string to convert.
     * @return string The converted PascalCase string.
     */
    public static function toPascalCase(string $string): string
    {
        return ucfirst((str_replace(' ', '', ucwords(str_replace('_', ' ', $string)))));
    }

    /**
     * Adds backticks to each element in a comma-separated string.
     *
     * @param $str
     * @return string
     */
    public static function addBackticks($str): string
    {
        // Split the string into an array by comma and space
        $elements = explode(', ', $str);

        // Add backticks to each element
        $elements = array_map(function ($el) {
            return "`$el`";
        }, $elements);

        // Join the elements back into a string with comma and space
        return implode(', ', $elements);
    }

    /**
     * Converts an array of values into a string of placeholders for use in prepared SQL statements.
     *
     * @param array $values The array of values to convert into placeholders.
     * @return string A comma-separated string of placeholders (e.g., "?, ?, ?").
     */
    public static function arrayToPlaceholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }

    /**
     * Processes an array of values and returns a formatted string with backticks and snake_case keys.
     *
     * @param array $values The associative array whose keys are processed.
     * @return string The formatted string with backticks and snake_case keys.
     */
    public static function formatArrayKeysWithBackticks(array $values): string
    {
        // Get the keys of the array and convert them to snake_case, then wrap them in backticks.
        return self::addBackticks(
            self::toSnakeCase(
                implode(
                    ', ',
                    array_keys($values)
                )
            )
        );
    }


    /**
     * Converts a database result and table structure into a class object.
     *
     * @param array $result The database result as an associative array.
     * @param array $tables An array where keys are table names and values are column mappings.
     * @return object The constructed class object based on the result and table structure.
     * @throws Exception If the base entity class or current entity class does not exist.
     */
    public function convertToClassObject(array $result, array $tables): object
    {
        $baseEntity = str_replace(
            ' ',
            '',
            ucwords(str_replace(['-', '_'], ' ', array_key_first($tables)))
        );

        $baseEntityInstance = new $baseEntity();

        foreach ($tables as $table => $columns) {
            $currentEntity = str_replace(
                ' ',
                '',
                ucwords(str_replace(['-', '_'], ' ', $table))
            );

            $currentInstance = new $currentEntity();

            $properties = (object)get_class_vars($currentEntity);

            if ($currentEntity === $baseEntity) {
                // Assign properties to instance
                foreach ($properties as $property => $value) {
                    foreach ($result as $propKey => $propVal) {
                        if (self::toCamelCase($propKey) === $property) {
                            $baseEntityInstance->$property = $propVal;
                        }
                    }
                }
            } else {
                // Assign properties to instance
                foreach ($properties as $property => $value) {
                    foreach ($result as $propKey => $propVal) {
                        if (self::toCamelCase($propKey) === $property) {
                            $currentInstance->$property = $propVal;
                        }
                    }
                }

                $baseEntityInstance->{$currentEntity} = $currentInstance;
            }
        }

        return $baseEntityInstance;
    }

    /**
     * Generates a debug string for the current query and its parameters.
     *
     * @return string The debug string containing the connection info and the query with parameters.
     */
    public static function debugQuery(string $query, ?array $params): string
    {
        foreach ($params as $param) {
            $type = gettype($param);

            if ($type === 'string') {
                $param = '"' . $param . '"';
            }

            $query = preg_replace("#\?#", $param, $query, 1);
        }

        return $query;
    }
}