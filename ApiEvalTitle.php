<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\Translate\PageTranslation\Hooks;

class ApiEvalTitle extends ApiBase {
	public function execute() {
		$params = $this->extractRequestParams();

		$page = $this->getTitleOrPageId( $params, 'fromdbmaster' );
		$title = $page->getTitle();
		if ( !$title->exists() ) {
			$this->dieWithError( 'notanarticle' );
		}

		$pm = MediaWikiServices::getInstance()->getPermissionManager();
		$user = $this->getUser();

		Hooks::$allowTargetEdit = true;
		$errors = $pm->getPermissionErrors( 'edit', $user, $title );
		Hooks::$allowTargetEdit = false;
		if ( $errors ) {
			$this->dieStatus( $this->errorArrayToStatus( $errors, $user ) );
		}

		TouhouPatchCenter::evalTitle( $title );
		return true;
	}

	public function isWriteMode() {
		return true;
	}
	public function mustBePosted() {
		return true;
	}
	public function needsToken() {
		return 'csrf';
	}

	public function getExamplesMessages() {
		return [
			'action=evaltitle&title=Main%20Page' => 'apihelp-evaltitle-example-title'
		];
	}
	public function getAllowedParams() {
		return array(
			'title' => array( ApiBase::PARAM_TYPE => 'string' ),
			'pageid' => array( ApiBase::PARAM_TYPE => 'integer' ),
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		);
	}
}
