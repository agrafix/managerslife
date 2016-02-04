<?php

/**
 * PHP5 Autoloader
 *
 * @param string $class_name
 */
function __php5_autoloader($class_name)
{
    $root = PATH . "/lib/";

    if ($class_name == "R" || $class_name == "RedBean_SimpleModel") {
    	require_once $root . "redbean/rb.php";
    	return;
    }

    $search_dirs = array(
        '{name}.class.php',
    	'../models/{name}.class.php',
        '../controllers/{name}.class.php',
    	'../controllers/ajax/{name}.class.php',
    	'../controllers/ajax/npc/{name}.class.php',
    	'../controllers/ajax/objects/{name}.class.php',
    	'../controllers/ajax/items/{name}.class.php',
        '../controllers/game/{name}.class.php',
        '../controllers/site/{name}.class.php'
    );

    foreach ($search_dirs as $dir)
    {
        $dir = str_replace('{name}', $class_name, $dir);

        if (file_exists($root . $dir))
        {
            require_once $root . $dir;
            break;
        }
    }
}

spl_autoload_register('__php5_autoloader');

/**
 * handy helper functions
 */
function formatCash($cash) {
	return number_format($cash, 0, ",", ".");
}

/**
 * PHP5.3 Fixes
 */
if ( false === function_exists('lcfirst') ) {
	function lcfirst( $str )
	{
		return (string)(strtolower(substr($str,0,1)).substr($str,1));
	}
}
?>
