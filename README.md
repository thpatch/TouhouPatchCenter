TouhouPatchCenter
=================

Creates a thcrap repository from MediaWiki pages.

System requirements
-------------------

The `{{stringdef}}` template hook supports an `|ascii` mode which automatically transliterates any
non-ASCII characters to their closest ASCII equivalents when writing the text to the patch files.
This transliteration requires a language locale (i.e., not `C`) to be installed on the system that
runs this extension. Otherwise, any non-ASCII characters will be silently discarded.\
Check `TPCFmtStrings::onDef()` for the hardcoded locale currently expected by the extension, which
should appear in the output of `locale -a` on the server.
