 <?php

/**
  * Flat-file server updating.
  *
  * @file
  * @author Nmlgc
  */

class TPCServer {
	/**
	 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	 * keys to arrays rather than overwriting the value in the first array with the duplicate
	 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
	 * this happens (documented behavior):
	 *
	 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('org value', 'new value'));
	 *
	 * arrayMergeRecursiveDistinct does not change the datatypes of the values in the arrays.
	 * Matching keys' values in the second array overwrite those in the first array, as is the
	 * case with array_merge, i.e.:
	 *
	 * arrayMergeRecursiveDistinct(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('new value'));
	 *
	 * Parameters are passed by reference, though only for performance reasons. They're not
	 * altered by this function.
	 *
	 * @param array $array1
	 * @param array $array2 Prioritized array
	 * @return array
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 */
	static function arrayMergeRecursiveDistinct( array &$array1, array &$array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => &$value ) {
			if ( 
				TPCUtil::isAssoc ( $value ) &&
				isset ( $merged[$key] ) &&
				TPCUtil::isAssoc ( $merged[$key] )
			) {
				$merged[$key] = self::arrayMergeRecursiveDistinct( $merged[$key], $value );
			} else 	{
				$merged[$key] = $value;
			}
		}
		return $merged;
	}

	/** 
	 * Recursively create a long directory path
	 */
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

	/**
	  * Returns the local path of the given server object.
	  */
	public static function getServerPath( &$server ) {
		return is_array( $server ) ? $server['path'] : $server;
	}

	protected static function mergeOldFile( $array, $fn ) {
		if ( !file_exists( $fn ) ) {
			return $array;
		}
		$oldJson = file_get_contents( $fn );
		if ( !$oldJson ) {
			return $array;
		}
		$oldArray = json_decode( $oldJson, true );
		return self::arrayMergeRecursiveDistinct( $oldArray, $array );
	}

	public static function getServersForPatch( &$patch ) {
		global $wgTPCServers;

		$ret = array();
		foreach ( $wgTPCServers as $i ) {
			if( !is_array( $i ) or !$i['url'] ) {
				continue;
			}
			$ret[] = "{$i['url']}/$patch/";
		}
		return $ret;
	}

	public static function chdirPatch( &$server, $patch, &$file ) {
		$curDir = "$server/$patch";
		self::createPath( $curDir );
		chdir( $curDir );
		self::createPath( dirname( $file ) );
	}

	/**
	  * Writes a JSON file to a certain patch, merging any previously created content.
	  *
	  * @return int Hash of the target file's full merged content.
	  */
	public static function writeJSONFile( $fn, &$array, $patch = null ) {
		global $wgTPCServers;

		// Don't write "null" for files that were requested but never edited
		if ( !$array or !$fn ) {
			return;
		}
		$renderFile = true;
		foreach ( $wgTPCServers as $server ) {
			$srvPath = self::getServerPath( $server );
			self::chdirPatch( $srvPath, $patch, $fn );			
			if ( $renderFile ) {
				// If this file already exists, merge its copy on the first server.
				$array = self::mergeOldFile( $array, $fn );
				$json = json_encode( (object)$array, TPC_JSON_OPTS );
				$renderFile = false;
				$ret = crc32( $json );
			}
			file_put_contents( $fn, $json, LOCK_EX );
		}
		return $ret;
	}

	public static function writeCopyFile( $target, &$source, $patch = null ) {
		global $wgTPCServers;

		foreach ( $wgTPCServers as $server ) {
			$srvPath = self::getServerPath( $server );
			self::chdirPatch( $srvPath, $patch, $target );
			copy( $source, $target );
		}
		return filemtime( $source );
	}

	/**
	  * @param function $cacheFunc
	  * 	Function to call for each element. Should return a hash or equivalent
	  * 	integer identifying the element's current version.
	  * 
	  * @param array $cache
	  * @param string $patch
	  * @return array Array of the form ( [filename] => [hash] )
	  */
	protected static function writeCache( $cacheFunc, &$cache, $patch = null ) {
		$ret = array();
		foreach ( $cache as $target => &$source ) {
			$hash = call_user_func( $cacheFunc, $target, $source, $patch );
			if ( $hash ) {	
				$ret[$target] = $hash;
			}
		}
		return $ret;
	}

	public static function writeJSONCache( &$jsonCache, $patch = null ) {
		return self::writeCache( 'self::writeJSONFile', $jsonCache, $patch );
	}

	public static function writeCopyCache( &$copyCache, $patch = null ) {
		return self::writeCache( 'self::writeCopyFile', $copyCache, $patch );
	}

	/**
	  * Updates the main server file (server.js).
	  * Also adds an optional $patchList.
	  */
	public static function writeServerFile( $patchList = null ) {
		global $wgTPCServers;
		global $wgTPCServerID;
		global $wgTPCServerDescURL;

		$serverCache = array();
		$serverJS = &$serverCache['server.js'];
		if ( $wgTPCServerID ) {
			$serverJS['id'] = $wgTPCServerID;
		}
		if ( $patchList ) {
			$serverJS['patches'] = $patchList;
		}
		foreach ( $wgTPCServers as $i ) {
			if ( !is_array( $i ) or !isset( $i['url'] ) ) {
				continue;
			}
			$serverJS['servers'][] = $i['url'];
		}
		if ( $wgTPCServerDescURL ) {
			$serverJS['url_desc'] = $wgTPCServerDescURL;
		}
		self::writeJSONCache( $serverCache );
	}

	/**
	  * The main "write state to all servers" function.
	  */
	public static function writeState( &$tpcState ) {
		global $wgTPCServers;

		$prevDir = getcwd();

		$files = $tpcState->listFiles();
		$patchJS = &$tpcState->patchJS;
		if ( empty ( $files ) and empty ( $patchJS )  ) {
			return;
		}

		// --------------
		// Other settings
		// --------------
		$patchJS['update'] = true;
		// List fonts
		$fonts = preg_grep( '/\.(ttf|otf)$/i', $files );
		// Nope, we can't do an array because this would overwrite any previous
		// assignment. It shouldn't matter for fonts, but it's still unexpected
		// behavior...
		foreach ( $fonts as $i ) {
			$patchJS['fonts'][$i] = true;
		}
		// --------------

		$patchList = array();

		foreach ( $tpcState->patches as $patch ) {
			// Write patch base URLs.
			// The if() is necessary because we do not want to accidentally null
			$servers = self::getServersForPatch( $patch );
			if ( $servers ) {
				$patchJS['servers'] = $servers;
			}
			$ret = array_merge(
				self::writeJSONCache( $tpcState->jsonCache, $patch ),
				self::writeCopyCache( $tpcState->copyCache, $patch )
			);
			if ( $ret ) {
				$patchJS['files'] = $ret;
			}

			// Whenever we have a title, we're evaluating just one patch anyway.
			// Yes, patches will not show up unless they have a thcrap_patch_info
			// associated with them.
			if ( isset( $patchJS['title'] ) ) {
				$patchList[$patch] = $patchJS['title'];
			}
			self::writeJSONFile( 'patch.js', $tpcState->patchJS, $patch );
		}
		self::writeServerFile( $patchList );

		// Shouldn't matter on the server, but offline testers will thank you
		chdir( $prevDir );
	}
}
