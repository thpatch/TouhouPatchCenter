-- Translatable source pages (in the original content language) whose
-- translation pages are evaluated when edited
CREATE TABLE /*_*/tpc_tl_source_pages (
	-- Page namespace
	tlsp_namespace int(11) NOT NULL,

	-- Page title
	tlsp_title varchar(255) NOT NULL,

	PRIMARY KEY (tlsp_namespace, tlsp_title)
) /*$wgDBTableOptions*/;
