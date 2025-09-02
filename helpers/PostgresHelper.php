<?php 
/**
 * Converts a php array into a postgres array (also multidimensional)
 * 
 * Each element is escaped using pg_escape_string, only string values
 * are enclosed within single quotes, numeric values no; special
 * elements as php nulls or booleans are literally converted, so the
 * php NULL value is written literally 'NULL' and becomes a postgres
 * NULL (the same thing is done with TRUE and FALSE values).
 *
 * Examples :
 * VARCHAR VERY BASTARD ARRAY :
 *    $input = array('bla bla', 'ehi "hello"', 'abc, def', ' \'VERY\' "BASTARD,\'value"', NULL);
 *
 *    to_pg_array($input) ==>> 'ARRAY['bla bla','ehi "hello"','abc, def',' ''VERY'' "BASTARD,''value"',NULL]'
 *
 *    try to put this value in a query (you will get a valid result):
 *    select unnest(ARRAY['bla bla','ehi "hello"','abc, def',' ''VERY'' "BASTARD,''value"',NULL]::varchar[])
 *
 * NUMERIC ARRAY:
 *    $input = array(1, 2, 3, 8.5, null, 7.32);
 *    to_pg_array($input) ==>> 'ARRAY[1,2,3,8.5,NULL,7.32]'
 *    try: select unnest(ARRAY[1,2,3,8.5,NULL,7.32]::numeric[])
 *
 * BOOLEAN ARRAY:
 *    $input = array(false, true, true, null);
 *    to_pg_array($input) ==>> 'ARRAY[FALSE,TRUE,TRUE,NULL]'
 *    try: select unnest(ARRAY[FALSE,TRUE,TRUE,NULL]::boolean[])
 *
 * MULTIDIMENSIONAL ARRAY:
 *    $input = array(array('abc', 'def'), array('ghi', 'jkl'));
 *    to_pg_array($input) ==>> 'ARRAY[ARRAY['abc','def'],ARRAY['ghi','jkl']]'
 *    try: select ARRAY[ARRAY['abc','def'],ARRAY['ghi','jkl']]::varchar[][]
 *
 * EMPTY ARRAY (is different than null!!!):
 *    $input = array();
 *    to_pg_array($input) ==>> 'ARRAY[]'
 *    try: select unnest(ARRAY[]::varchar[])
 *
 * NULL VALUE :
 *    $input = NULL;
 *    to_pg_array($input) ==>> 'NULL'
 *    the functions returns a string='NULL' (literally 'NULL'), so putting it
 *    in the query, it becomes a postgres null value.
 * 
 * If you pass a value that is not an array, the function returns a literal 'NULL'.    
 * 
 * You should put the result of this functions directly inside a query,
 * without quoting or escaping it and you cannot use this result as parameter
 * of a prepared statement.
 *
 * Example:
 * $q = 'INSERT INTO foo (field1, field_array) VALUES ($1, ' . to_pg_array($php_array) . '::varchar[])';
 * $params = array('scalar_parameter');
 * 
 * It is recommended to write the array type (ex. varchar[], numeric[], ...) 
 * because if the array is empty or contains only null values, postgres
 * can give an error (cannot determine type of an empty array...)
 * 
 * The function returns only a syntactically well-formed array, it does not
 * make any logical check, you should consider that postgres gives errors
 * if you mix different types (ex. numeric and text) or different dimensions
 * in a multidim array.
 *
 * @param array $set PHP array
 * 
 * @return string Array in postgres syntax
 */

namespace common\helpers;

class PostgresHelper {

    public static function to_pg_array($set) {

        if (is_null($set) || !is_array($set)) {
            return 'NULL';
        }
    
        // can be called with a scalar or array
        settype($set, 'array');
    
        $result = array();
        foreach ($set as $t) {
                // Element is array : recursion
            if (is_array($t)) {
                $result[] = to_pg_array($t);
            }
            else {
                // PHP NULL
                if (is_null($t)) {
                    $result[] = 'NULL';
                }
                // PHP TRUE::boolean
                elseif (is_bool($t) && $t == TRUE) {
                    $result[] = 'TRUE';
                }
                // PHP FALSE::boolean
                elseif (is_bool($t) && $t == FALSE) {
                    $result[] = 'FALSE';
                }
                // Other scalar value
                else {
                    // Escape
                    //$t = pg_escape_string($t);
    
                    // quote only non-numeric values
                    if (!is_numeric($t)) {
                        $t = '\'' . $t . '\'';
                    }
                    $result[] = $t;
                }
            }
        }
        return 'ARRAY[' . implode(",", $result) . ']'; // format
    }

 }
