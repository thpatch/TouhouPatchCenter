<?php

/**
  * Base class for templates rendered by the extension.
  *
  * @file
  * @author Nmlgc
  */

abstract class TPCTemplate {
	abstract public static function run( &$parser, &$cache, &$magicWordId, &$ret, &$frame );

	public static function runSubclass( &$parser, &$cache, &$magicWordId, &$ret, &$frame ) {
		if ( is_subclass_of( $magicWordId, get_called_class() ) ) {
			// Necessary workaround to use references with call_user_func()
			$refWrap = array( &$parser, &$cache, &$magicWordId, &$ret, &$frame );
			return call_user_func_array( "$magicWordId::run", $refWrap );
		}
		return true;
	}

	/**
	  * GetMagicVariableIDs hook.
	  */
	public static function setup( &$variableIDs ) {
		$variableIDs[] = get_called_class();
		return true;
	}
}
