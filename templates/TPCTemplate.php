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
			return $magicWordId::run( $parser, $cache, $magicWordId, $ret, $frame );
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
