<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Playa to Relationships Module Install/Update File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		EllisLab Dev Team
 * @link
 */

class El_playamatrix_importer_upd {

	public $version = '1.0';
	private $module_name = 'El_playamatrix_importer';

	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		$mod_data = array(
			'module_name'			=> $this->module_name,
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields'	=> 'n'
		);

		ee()->db->insert('modules', $mod_data);

		return TRUE;
	}

	// ----------------------------------------------------------------

	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */
	public function uninstall()
	{
		$mod_id = ee()->db->select('module_id')
			->get_where('modules', array(
				'module_name'	=> $this->module_name
			))->row('module_id');

		if (ee()->db->table_exists('module_member_groups')) {
			ee()->db->where('module_id', $mod_id)
				->delete('module_member_groups');
		}	
		
		if (ee()->db->table_exists('module_member_roles')) {
			ee()->db->where('module_id', $mod_id)
				->delete('module_member_roles');
		}

		ee()->db->where('module_name', $this->module_name)
			->delete('modules');

		return TRUE;
	}
}
// EOF