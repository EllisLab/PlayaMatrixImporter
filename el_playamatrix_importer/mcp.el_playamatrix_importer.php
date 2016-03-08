<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD.'el_playamatrix_importer/libraries/autoload.php');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.10.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Playa to Relationships Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class El_playamatrix_importer_mcp {

	public $return_data;

	private $_base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_form_base_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=el_playamatrix_importer';
		$this->_base_url = BASE.AMP.$this->_form_base_url;

		ee()->cp->set_right_nav(array('module_home' => $this->_base_url));
		ee()->view->cp_page_title = lang('el_playamatrix_importer_module_name');
	}

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		ee()->load->model('addons_model');

		if ( ! ee()->addons_model->fieldtype_installed('grid') OR ! ee()->addons_model->fieldtype_installed('relationship'))
		{
			return '<p class="notice">'.lang('grid_relationships_not_installed').'</p>';
		}

		$success_message = '';

		if (ee()->session->flashdata('success'))
		{
			$success_message = '<p class="success"><b>'.ee()->session->flashdata('success').'</b></p>';
		}

		return $success_message.
			lang('el_playamatrix_importer_module_long_description')
			.form_open($this->_form_base_url.AMP.'method=do_import')
			.form_submit('submit', lang('btn_import'), 'class="submit"')
			.form_close();
	}

	/**
	 * Does the import
	 *
	 * @return 	void
	 */
	public function do_import()
	{
		// Attempt to get more time
		@set_time_limit(0);

		// The install I was testing this on had an ff_settings field which
		// was set to NOT NULL, causing us not to be able to add new fields;
		// not sure how that happened, so putting this here just in case
		if (ee()->db->field_exists('ff_settings', 'channel_fields'))
		{
			ee()->load->library('smartforge');
			ee()->smartforge->modify_column(
				'channel_fields',
				array(
					'ff_settings'	=> array(
						'name'		=> 'ff_settings',
						'type'		=> 'mediumtext',
						'null'		=> TRUE
					)
				)
			);
		}

		ee()->load->library(array('playa_importer', 'matrix_importer'));

		// Do the import
		$new_relationship_ids = ee()->playa_importer->do_import();
		$new_grid_ids = ee()->matrix_importer->do_import();

		ee()->session->set_flashdata('success', sprintf(lang('import_completed'), count($new_relationship_ids), count($new_grid_ids)));
		ee()->functions->redirect($this->_base_url);
	}

}
// EOF