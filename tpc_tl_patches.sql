-- Translation patch list table
CREATE TABLE /*_*/tpc_tl_patches (
  -- Patch name
  tl_patch varchar(255) UNIQUE,

  -- Language code
  tl_code varchar(255) UNIQUE,

  PRIMARY KEY (tl_patch, tl_code)
)  /*$wgDBTableOptions*/;

