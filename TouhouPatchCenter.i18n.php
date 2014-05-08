<?php
/**
 * Translations for the Touhou Patch Center extension.
 *
 * @file
 */

$messages = array();
$magicWords = array();

/** English (English) */
$messages['en'] = array(
	'group-patchdev' => 'Patch developers',
	'group-patchdev-member' => 'Patch developer',
	'grouppage-patchdev' => '{{ns:project}}:Patch developers',
	'right-tpc-restricted' => 'Add and modify restricted patch templates',
	'tpc-desc' => "Creates a repository for the ''Touhou Community Reliant Automatic Patcher'' from MediaWiki pages.",
	'tpc-edit-blocked' => "You do not have permission to add, remove or modify the content of restricted patch templates. These are:\n\n{{thcrap_restricted_templates}}\n\nYou can, however, use the corresponding <tt>/test</tt> subtemplates, which are rendered identically, but not parsed for patches.",
	'tpc-template' => '<code>&#123;&#123;$1&#125;&#125;</code>'
);
$magicWords['en'] = array(
	'thcrap_neighbors' => array( 0, 'thcrap_neighbors' ),
	'thcrap_servers' => array( 0, 'thcrap_servers' ),
	'thcrap_restricted_templates' => array( 0, 'thcrap_restricted_templates' )
);

/** German (Deutsch) */
$messages['de'] = array(
	'group-patchdev' => 'Patch-Entwickler',
	'group-patchdev-member' => '{{GENDER:$1|Patch-Entwickler|Patch-Entwicklerin}}',
	'grouppage-patchdev' => '{{ns:project}}:Patch-Entwickler',
	'right-tpc-restricted' => 'Beschränkte Patch-Vorlagen hinzufügen und bearbeiten',
	'tpc-desc' => "Erstellt einen Server für den ''Touhou Community Reliant Automatic Patcher'' aus MediaWiki-Seiten.",
	'tpc-edit-blocked' => 'Du bist nicht berechtigt, beschränkte Patch-Vorlagen hinzuzufügen, zu löschen, oder deren Inhalt zu ändern. Zu diesen Vorlagen gehören:\n\n$1\n\nDu kannst jedoch die entsprechendenden <tt>/test</tt>-Untervorlagen nutzen. Diese werden identisch dargestellt, aber nicht in Patches einbezogen.'
);
