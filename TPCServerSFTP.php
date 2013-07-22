<?php

/**
  * SFTP storage back-end, using phpseclib (http://phpseclib.sourceforge.net).
  *
  * @file
  * @author Nmlgc
  */

class TPCServerSFTP extends TPCServer {

	// SFTP connection
	protected $sftp = null;

	// Current directory
	protected $dir = null;

	/// ===================
	/// TPCServer functions
	/// ===================

	/**
	  * Initializes an SFTP session.
	  */
	function __construct( array &$serverInfo ) {
		global $wgTPCServerRSAPrivKey;
		global $wgTPCServerRSAPass;

		// Required $serverInfo elements
		$sftp_host = &$serverInfo['sftp_host'];
		$sftp_user = &$serverInfo['sftp_user'];
		$sftp_pass = TPCUtil::dictGet( $serverInfo['sftp_pass'] );
		$local_path = TPCUtil::dictGet( $serverInfo['local_path'] );

		if ( !$sftp_pass and $wgTPCServerRSAPrivKey ) {
			$key = new Crypt_RSA();
			$key->setPassword( $wgTPCServerRSAPass );
			$key->loadKey( file_get_contents( $wgTPCServerRSAPrivKey ) );
		} else {
			$key = null;
		}

		$this->sftp = new Net_SFTP( $sftp_host );

		if ( $sftp_pass ) {
			$ret = $this->sftp->login( $sftp_user, $sftp_pass );
		} else if ( $key ) {
			$ret = $this->sftp->login( $sftp_user, $key );
		} else {
			$ret = $this->sftp->login( $sftp_user );
		}

		if ( $local_path ) {
			$this->sftp->chdir( $local_path );
		}
	}

	function wipe() {
		$files = $this->sftp->nlist();
		foreach ( $files as $i ) {
			if ( $i != '.' and $i != '..' ) {
				$this->sftp->delete( $i, true );
			}
		}
	}

	function mkdir( &$dir ) {
		if ( !empty( $dir ) ) {
			$this->sftp->mkdir( $dir, -1, true );
		}
	}

	function chdir( &$dir ) {
		if ( $dir ) {
			$this->dir = $dir . '/';
		} else {
			$this->dir = null;
		}
	}

	function put( &$fn, &$data ) {
		$this->sftp->put( $this->dir . $fn, $data );
	}

	function get( &$fn ) {
		return $this->sftp->get( $this->dir . $fn );
	}

	function copy( &$target, &$source ) {
		$this->sftp->put( $this->dir . $target, $source, NET_SFTP_LOCAL_FILE );
	}
};
