<?php
/**
 * Bootstrap for PHPUnit testing.
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// Include the Store.icu module.
require_once __DIR__ . '/../store_icu.php';

/**
 * Mock the WHMCS database Capsule class
 */
if (!class_exists('WHMCS\Database\Capsule')) {
    class WHMCS\Database\Capsule
    {
        public static function table($table)
        {
            return new MockQueryBuilder($table);
        }
        
        public static function schema()
        {
            return new MockSchemaBuilder();
        }
    }
    
    class MockQueryBuilder
    {
        protected $table;
        
        public function __construct($table)
        {
            $this->table = $table;
        }
        
        public function where()
        {
            return $this;
        }
        
        public function first()
        {
            return null;
        }
        
        public function get()
        {
            return array();
        }
        
        public function insert($values)
        {
            return true;
        }
        
        public function update($values)
        {
            return true;
        }
    }
    
    class MockSchemaBuilder
    {
        public function hasTable($table)
        {
            return false;
        }
        
        public function create($table, $callback)
        {
            return true;
        }
    }
}

/**
 * Mock logModuleCall function for testing purposes.
 *
 * @param string $module
 * @param string $action
 * @param string|array $request
 * @param string|array $response
 * @param string|array $data
 * @param array $variablesToMask
 *
 * @return void|false
 */
function logModuleCall(
    $module,
    $action,
    $request,
    $response,
    $data = '',
    $variablesToMask = array()
) {
    // Do nothing during tests
}

/**
 * Mock select_query function
 */
function select_query($table, $fields, $where)
{
    return true;
}

/**
 * Mock mysql_fetch_array function
 */
function mysql_fetch_array($result)
{
    return array();
}

/**
 * Mock update_query function
 */
function update_query($table, $values, $where)
{
    return true;
}
