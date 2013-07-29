<?php

/**
  * Simply calls TouhouPatchCenter::evalTitle on a given title. :-)
  *
  * @file
  * @author Nmlgc
  */

require_once( dirname( __FILE__ ) . "/../../../maintenance/Maintenance.php" );

class TPCEvalTitle extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addOption( 'title', 'Title of the page to evaluate', true, false );
	}

	public function execute() {
		$text = $this->getOption( 'title' );
		if ( !$text ) {
			return;
		}
		$title = Title::newFromText( $text );
		if ( $title->isKnown() ) {
			TouhouPatchCenter::evalTitle( $title );
		} else {
			$this->output( "Page '$text' doesn't exist!\n" );
		}
		return;
	}
}

$maintClass = 'TPCEvalTitle';
require_once( RUN_MAINTENANCE_IF_MAIN );
