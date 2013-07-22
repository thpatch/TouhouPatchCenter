<?php

/**
  * Base class for server storage back-ends.
  *
  * @file
  * @author Nmlgc
  */

abstract class TPCServer {

	abstract function __construct( array &$serverInfo );

	// All target file names and directories are relative to the server root.

	// Deletes everything in the server directory.
	abstract function wipe();

	// Always recursive.
	abstract function mkdir( &$dir );
	abstract function chdir( &$dir );

	abstract function put( &$fn, &$data );
	abstract function get( &$fn );
	abstract function copy( &$target, &$source );
};
