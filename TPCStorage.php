<?php

/**
  * Flat-file storage front-end.
  *
  * @file
  * @author Nmlgc
  */

class TPCStorage {

	const JSON_OPTS = (JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

	// TPCServer objects.
	static protected $servers = null;

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
	 * @param array $array1 Base array.
	 * @param array $array2 Prioritized array.
	 * @param bool $changed Receives true if there has been a change.
	 * @return array
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 */
	public static function arrayMergeRecursiveDistinct( array &$array1, array &$array2, &$changed ) {
		$merged = $array1;
		foreach ( $array2 as $key => &$value ) {
			if (
				TPCUtil::isAssoc ( $value ) &&
				isset ( $merged[$key] ) &&
				TPCUtil::isAssoc ( $merged[$key] )
			) {
				$merged[$key] = self::arrayMergeRecursiveDistinct( $merged[$key], $value, $changed );
			} else if ( !isset( $merged[$key] ) or $merged[$key] !== $value ) {
				$merged[$key] = $value;
				$changed = true;
			}
		}
		return $merged;
	}

	protected static function mergeOldFile( &$server, &$array, &$fn, &$changed ) {
		if ( !file_exists( $fn ) ) {
			$changed = true;
			return $array;
		}
		$oldJson = $server->get( $fn );
		if ( !$oldJson ) {
			$changed = true;
			return $array;
		}
		$oldArray = json_decode( $oldJson, true );
		if ( $oldArray === null ) {
			throw new MWException(
				"`$fn` is invalid JSON. Ask an admin to fix the error on the server."
			);
		}
		return self::arrayMergeRecursiveDistinct( $oldArray, $array, $changed );
	}

	protected static function getServersForPatch( &$patch ) {
		global $wgTPCServers;

		$ret = array();
		foreach ( $wgTPCServers as $i ) {
			if ( isset( $i['url'] ) ) {
				$serverURL = "{$i['url']}/$patch/";
				$ret[] = preg_replace( '/([^:])(\/{2,})/', '$1/', $serverURL );
			}
		}
		return $ret;
	}

	protected static function chdirPatch( &$server, $patch, &$file ) {
		$curDir = '';
		if ( $patch ) {
			$curDir = $patch;
		}
		$server->mkdir( $patch );
		$server->chdir( $patch );
		// Current directory is now patch-relative, don't go further.
		// Create file's directory if necessary - but don't change to it!
		$dirName = dirname( $file );
		if ( $dirName and $dirName != '.' ) {
			$curDir .= '/' . $dirName;
		}
		$server->mkdir( $curDir );
	}

	/**
	  * Writes a JSON file to a certain patch, merging any previously created content.
	  *
	  * @return int Hash of the target file's full merged content.
	  */
	protected static function writeJSONFile( $fn, &$array, $patch = null ) {
		$ret = null;
		// Don't write "null" for files that were requested but never edited
		if ( !$array or !$fn ) {
			return;
		}
		$renderFile = true;
		foreach ( self::$servers as $server ) {
			self::chdirPatch( $server, $patch, $fn );
			if ( $renderFile ) {
				// If this file already exists, merge its copy on the first server.
				$changed = false;
				$array = self::mergeOldFile( $server, $array, $fn, $changed );
				if( !$changed ) {
					// Nothing to do here.
					return;
				}
				$json = json_encode( (object)$array, self::JSON_OPTS );
				$renderFile = false;
				$ret = crc32( $json );
			}
			$server->put( $fn, $json );
		}
		return $ret;
	}

	protected static function writeCopyFile( $target, &$source, $patch = null ) {
		foreach ( self::$servers as $server ) {
			self::chdirPatch( $server, $patch, $target );
			$server->copy( $target, $source );
		}
		$sourceData = file_get_contents( $source );
		return crc32( $sourceData );
	}

	protected static function writeDeletion( $target, $patch = null ) {
		foreach ( self::$servers as $server ) {
			self::chdirPatch( $server, $patch, $target );
			$server->delete( $target );
		}
	}

	/**
	  * @param function $cacheFunc
	  * 	Function to call for each element. Should return a hash or equivalent
	  * 	integer identifying the element's current version.
	  *
	  * @param string $cacheFunc Name of the cache function. Must be a static method of this
	  *   class.
	  * @param array $cache
	  * @param string $patch
	  * @return array Array of the form ( [filename] => [hash] )
	  */
	protected static function writeCache( $cacheFunc, &$cache, $patch = null ) {
		$ret = array();
		foreach ( $cache as $target => &$source ) {
			$hash = self::$cacheFunc( $target, $source, $patch );
			if ( $hash ) {
				$ret[$target] = $hash;
			}
		}
		return $ret;
	}

	protected static function writeJSONCache( &$jsonCache, $patch = null ) {
		return self::writeCache( 'writeJSONFile', $jsonCache, $patch );
	}

	protected static function writeCopyCache( &$copyCache, $patch = null ) {
		return self::writeCache( 'writeCopyFile', $copyCache, $patch );
	}

	protected static function writeDeletionCache( &$deletionCache, $patch = null ) {
		$ret = array();
		foreach ( $deletionCache as $i ) {
			self::writeDeletion( $i, $patch );
			$ret[ $i ] = null;
		}
		return $ret;
	}

	/**
	  * Updates the main repository definition (repo.js).
	  * Also adds an optional $patchList.
	  */
	protected static function writeRepoFile( $patchList = null ) {
		global $wgTPCServers;
		global $wgTPCRepoID;
		global $wgTPCRepoTitle;
		global $wgTPCRepoContact;
		global $wgTPCRepoNeighbors;
		global $wgTPCRepoDescURL;

		$repoJS = array(
			'id' => $wgTPCRepoID,
			'title' => $wgTPCRepoTitle,
			'contact' => $wgTPCRepoContact,
			'neighbors' => $wgTPCRepoNeighbors,
			'url_desc' => $wgTPCRepoDescURL
		);

		if ( $patchList ) {
			$repoJS['patches'] = $patchList;
		}

		foreach ( $wgTPCServers as $i ) {
			if ( isset( $i['url'] ) ) {
				$repoJS['servers'][] = $i['url'];
			}
		}
		self::writeJSONFile( 'repo.js', $repoJS );
	}

	protected static function newServer( &$server ) {
		$class = $server['class'];
		if ( class_exists( $class ) ) {
			return new $class( $server );
		} else if ( is_string( $class ) ) {
			throw new MWException(
				"Required back-end server class '$class' not available!\n" .
				"(Did you run 'composer install'?)"
			);
		}
	}

	/**
	  * Initializes the server back-end classes.
	  */
	public static function init() {
		global $wgTPCServers;

		if ( self::$servers ) {
			return;
		}
		self::$servers = array();
		foreach ( $wgTPCServers as $i ) {
			if ( isset( $i['class'] ) ) {
				self::$servers[] = self::newServer( $i );
			}
		}
	}

	/**
	  * The main "write state to all servers" function.
	  */
	public static function writeState( &$tpcState ) {
		$prevDir = getcwd();

		$files = $tpcState->listFiles();
		$patchJS = &$tpcState->getFile( null, 'patch.js' );
		if ( empty ( $files ) and empty ( $patchJS ) ) {
			return;
		}

		self::init();

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
			// Whenever we have a title, we're evaluating just one patch anyway.
			// Yes, patches will not show up unless they have a thcrap_patch_info
			// associated with them.
			if ( isset( $patchJS['title'] ) ) {
				$patchList[$patch] = $patchJS['title'];
			}
			$filesJS = array_merge(
				self::writeJSONCache( $tpcState->jsonCache, $patch ),
				self::writeCopyCache( $tpcState->copyCache, $patch ),
				self::writeDeletionCache( $tpcState->deletionCache, $patch )
			);
			self::writeJSONFile( 'files.js', $filesJS, $patch );
		}
		self::writeRepoFile( $patchList );

		// Shouldn't matter on the server, but offline testers will thank you
		chdir( $prevDir );
	}

	/// =========================
	/// Wrappers around TPCServer
	/// =========================

	public static function wipe() {
		foreach ( self::$servers as $server ) {
			$server->wipe();
		}
	}
}
