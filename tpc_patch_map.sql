-- Page -> Patch mapping table
CREATE TABLE /*_*/tpc_patch_map (
  -- Page namespace
  pm_namespace int(11) NOT NULL DEFAULT '0',

  -- Page title
  pm_title varchar(255) NOT NULL DEFAULT '',

  -- Patches that include this page, separated by \n
  pm_patch mediumblob,

  -- Default game of this page
  pm_game text,

  -- Target name for direct file includes
  pm_target mediumblob,

  PRIMARY KEY (pm_namespace, pm_title)
)  /*$wgDBTableOptions*/;
