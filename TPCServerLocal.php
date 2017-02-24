<?php

/**
  * Local file system storage back-end.
  *
  * @file
  * @author Nmlgc
  */

class TPCServerLocal extends TPCServer {

	// Local root path.
	protected $rootPath = null;

	/// ===================
	/// Recursive functions
	/// ===================

	// Recursively create a long directory path
	protected static function createPath( $path ) {
		if ( is_dir( $path ) ) {
			return true;
		}
		$len = strrpos( $path, '/', -2 );
		if ( $len ) {
			$prevPath = substr( $path, 0, $len + 1 );
			self::createPath( $prevPath );
		}
		// Why PHP decided that an empty path is worthy of a warning is beyond me
		return $path ? mkdir( $path ) : true;
	}

	// ------------ lixlpixel recursive PHP functions -------------
	// removePath( directory to delete, empty )
	// ------------------------------------------------------------
	public static function removePath( $path, $empty = FALSE )
	{
		if ( substr( $path, -1 ) == '/' ) {
			$path = substr( $path, 0, -1 );
		}
		if ( !file_exists ( $path ) || !is_dir ( $path ) ) {
			return FALSE;
		} elseif ( is_readable( $path ) ) {
			$handle = opendir( $path );
			while ( FALSE !== ( $item = readdir( $handle ) ) ) {
				if ( $item != '.' && $item != '..' ) {
					$curPath = $path . '/' . $item;
					if ( is_dir ( $curPath ) ) {
						self::removePath( $curPath );
					} else {
						unlink( $curPath );
					}
				}
			}
			closedir( $handle );
			if ( $empty == FALSE ) {
				if ( !rmdir( $path ) ) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	// ------------------------------------------------------------

	/// ===================
	/// TPCServer functions
	/// ===================

	function __construct( array &$serverInfo ) {
		$this->rootPath = $serverInfo['local_path'];
	}

	function wipe() {
		self::removePath( $this->rootPath, true );
	}

	function mkdir( &$dir ) {
		return self::createPath( $this->rootPath . $dir );
	}

	function chdir( &$dir ) {
		chdir( $this->rootPath . $dir );
	}

	function put( &$fn, &$data ) {
		file_put_contents( $fn, $data, LOCK_EX );
	}

	function get( &$fn ) {
		return file_get_contents( $fn );
	}

	function copy( &$target, &$source ) {
		copy( $source, $target );
	}

	function delete( &$target ) {
		unlink( $target );
	}
};
