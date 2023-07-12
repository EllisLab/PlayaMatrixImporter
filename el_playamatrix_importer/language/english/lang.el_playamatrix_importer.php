<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(
	'el_playamatrix_importer_module_name' =>
	'Playa & Matrix Importer',

	'el_playamatrix_importer_module_description' =>
	'Creates new Relationships and Grid fields from Playa and Matrix fields.',

	'el_playamatrix_importer_module_long_description' => "<p>This module will import existing Playa and Matrix fields into new Relationship and Grid fields. The import is <b>non-destructive</b>, meaning it doesn't convert or change the existing fields or data in any way. It creates <b>new</b> fields and copies the data over to them, so you have a chance to inspect and check that the import has done its job before you commit to the new fields.</p><p>Backup your database in case you want to rollback, then click Import to get started.</p>",

	'el_playamatrix_fields' => 'Playa & Matrix Fields',

	'module_home' => 'Playa & Matrix Importer Home',

	'grid_relationships_not_installed' => 'Please make sure both Grid and Relationships are installed before importing.',

	'btn_import' => 'Import',

	'import_success' => 'Import Successful',
	'import_fail' => 'Import Failed',
	'import_completed' => 'Success: Import has created %d new Relationship fields and %d new Grid fields',
	'import_no_fields' => 'Failed: No Matrix or Playa fields selected'
);

// EOF