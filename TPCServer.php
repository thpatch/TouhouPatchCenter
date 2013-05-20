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
				is_array ( $value ) &&
				isset ( $merged[$key] ) &&
				is_array ( $merged[$key] )
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

	public function getServersForPatch( &$patch ) {
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
	  * Writes a JSON patch cache to a certain patch.
	  */
	public static function writeJSONCache( &$jsonCache, $patch = null ) {
		global $wgTPCServers;

		// First loop files, then servers.
		// This allows stacked files (especially the main game .js files)
		// to have more differences between patches.
		foreach ( $jsonCache as $fn => $array ) {
			// Don't write "null" for files that were requested but never edited
			if ( !$array or !$fn ) {
				continue;
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
				}
				file_put_contents( $fn, $json, LOCK_EX );
			}
		}
	}

	protected static function writeCopyCache( &$copyCache, $patch = null ) {
		global $wgTPCServers;

		// Loop order doesn't matter here.
		foreach ( $copyCache as $target => $source ) {
			foreach ( $wgTPCServers as $server ) {
				$srvPath = self::getServerPath( $server );
				self::chdirPatch( $srvPath, $patch, $target );

				copy( $source, $target );
			}
		}
	}

	/**
	  * Updates the main server file (server.js).
	  * Also adds an optional $patchList.
	  */
	public static function writeServerFile( $patchList = null ) {
		global $wgTPCServers;
		global $wgTPCServerID;

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
		self::writeJSONCache( $serverCache );
	}

	/**
	  * The main "write state to all servers" function.
	  */
	public static function writeState( &$tpcState ) {
		global $wgTPCServers;

		$prevDir = getcwd();

		$files = $tpcState->listFiles();
		if ( empty ( $files ) ) {
			return;
		}
		$patchJS = &$tpcState->getFile( null, 'patch.js' );
		$patchJS['files'] = $files;

		// --------------
		// Other settings
		// --------------
		$patchJS['update'] = true;
		// List fonts
		$fonts = preg_grep( '/\.(ttf|otf)$/i', array_keys( $files ) );
		// Nope, we can't do an array because this would overwrite any previous
		// assignment. It shouldn't matter for fonts, but it's still unexpected
		// behavior...
		foreach ( $fonts as $i ) {
			$patchJS['fonts'][$i] = true;
		}
		// --------------

		$patchList = array();

		foreach ( $tpcState->patches as $patch ) {
			/**
			  * Decided against this.
			  * Secondary mirroring will be pretty annoying if people have to 
			  * change the servers in every patch.js on every update.
			  *
			// Write patch base URLs.
			// The if() is necessary because we do not want to accidentally null
			$servers = self::getServersForPatch( $patch );
			if ( $servers ) {
				$patchJS['servers'] = $servers;
			}
			*/
			self::writeJSONCache( $tpcState->jsonCache, $patch );
			self::writeCopyCache( $tpcState->copyCache, $patch );

			// Whenever we have a title, we're evaluating just one patch anyway.
			// Yes, patches will not show up unless they have a thcrap_patch_info
			// associated with them.
			if ( isset( $patchJS['title'] ) ) {
				$patchList[$patch] = $patchJS['title'];
			}
		}
		self::writeServerFile( $patchList );

		// Shouldn't matter on the server, but offline testers will thank you
		chdir( $prevDir );
	}
}
