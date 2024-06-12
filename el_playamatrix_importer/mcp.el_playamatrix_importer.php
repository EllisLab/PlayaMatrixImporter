<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD.'el_playamatrix_importer/libraries/autoload.php');

/**
 * ExpressionEngine - by Packet Tide
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2013, Packet Tide, LLC
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://packettide.com
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
 * @author		Packet Tide
 * @link		http://packettide.com
 */

class El_playamatrix_importer_mcp {

	public $return_data;

	private $_base_url;	
	private $site_id = 1;
	private $_form_base_url;
	
	private $EE3 = FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (defined('APP_VER') && version_compare(APP_VER, '3.0.0', '>='))
		{
			$this->EE3 = TRUE;
		}
		
		$this->site_id = ee()->config->item('site_id');
		
		$this->_form_base_url = ($this->EE3) ?  ee('CP/URL', 'addons/settings/el_playamatrix_importer') : 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=el_playamatrix_importer';
		$this->_base_url = ($this->EE3) ? $this->_form_base_url : BASE.AMP.$this->_form_base_url;

		if ($this->EE3)
		{
			ee()->cp->set_right_nav(array('module_home' => $this->_base_url));
			ee()->view->cp_page_title = lang('el_playamatrix_importer_module_name');
		}
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
	
		$fields = array();

		$channel_fields = ee()->db
			->select('field_id, field_name, field_label, field_type')
			->where_in('field_type', array('matrix', 'playa'))
			->where_in('site_id', array(0, $this->site_id))
			->order_by('field_id')
			->get('channel_fields')
			->result_array();

		foreach ($channel_fields as $channel_field)
		{
			$fields[$channel_field['field_id']] = '['.$channel_field['field_id'].'] '.$channel_field['field_label'].': '.$channel_field['field_name'].' - ['.$channel_field['field_type'].']';
		}
		
		$text_fields = ee()->db
			->select('field_id, field_name, field_label, field_type')
			->where_in('field_type', array('text', 'textarea'))
			->where_in('site_id', array(0, $this->site_id))
			->order_by('field_id')
			->get('channel_fields')
			->result_array();

		foreach ($text_fields as $text_field)
		{
			$target_field[$text_field['field_id']] = '['.$text_field['field_id'].'] '.$text_field['field_label'].': '.$text_field['field_name'].' - ['.$text_field['field_type'].']';
		}
		
		
		if ($this->EE3)
		{
		
			// Form definition array
			$vars['sections'] = array(
			  array(
					  
				array(
				  'title' => 'el_playamatrix_fields',
				  'desc' => 'el_playamatrix_importer_module_long_description',
				  'fields' => array(
					'fields' => array(
					  'type' => 'checkbox',
					  'choices' => $fields,
					  //'value' => $checked_values
					)
				  )
				)
				
			  )
			);

			// Final view variables we need to render the form
			$vars += array(
			  'base_url' => $this->_base_url.AMP.'method=do_import',
			  'cp_page_title' => lang('el_playamatrix_importer_module_description'),
			  'save_btn_text' => 'btn_import',
			  'save_btn_text_working' => 'btn_import'
			);

			return ee('View')->make('ee:_shared/form')->render($vars);
		
		}
		else
		{
			ee()->load->library('table');
			ee()->load->helper('form');
			
			ee()->view->cp_page_title = ee()->lang->line('el_playamatrix_importer_module_name');
		
			$message = '';

			if (ee()->session->flashdata('message_success'))
			{
				$message = '<p class="notice success"><b>'.ee()->session->flashdata('message_success').'</b></p>';
			}
			elseif (ee()->session->flashdata('message_error'))
			{
				$message = '<p class="notice failure"><b>'.ee()->session->flashdata('message_error').'</b></p>';
			}
		
			$vars['message'] = $message;
			$vars['fields'] = $fields;
			$vars['form_action'] = $this->_form_base_url.AMP.'method=do_import';

			return ee()->load->view('index', $vars, TRUE);

		}
		

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
		
		$fields = ee()->input->post('fields');
		
		if (is_array($fields) && !empty($fields))
		{

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

			ee()->load->model('addons_model');

			$new_relationship_ids = array();
			$new_grid_ids = array();

			// Do the import
			if (ee()->addons_model->fieldtype_installed('playa'))
			{
				$new_relationship_ids = ee()->playa_importer->do_import($fields);
			}

			if (ee()->addons_model->fieldtype_installed('matrix'))
			{
				$new_grid_ids = ee()->matrix_importer->do_import($fields);
			}
			
			// Update field groups
			$this->import_field_groups($new_relationship_ids+$new_grid_ids);

			if ($this->EE3) 
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('import_success'))
					->addToBody(sprintf(lang('import_completed'), count($new_relationship_ids), count($new_grid_ids)))
					->defer();
			}
			else
			{
				ee()->session->set_flashdata('success', sprintf(lang('import_completed'), count($new_relationship_ids), count($new_grid_ids)));
			}
		}
		else
		{
			if ($this->EE3) 
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('import_fail'))
					->addToBody(lang('import_no_fields'))
					->defer();
			}
			else
			{
				ee()->session->set_flashdata('success', 'import_no_fields');
			}
			
		}

		ee()->functions->redirect($this->_base_url);
	}
	
	/**
	 * Copies over field groups from Matrix fields into their new Grid fields'
	 *
	 * @param	array	Associative array of Matrix field IDs to their corresponding Grid field IDs
	 * @return	void
	 */
	private function import_field_groups($imported_fields)
	{
		foreach ($imported_fields as $old_field_id => $new_field_id) 
		{
			if (ee()->db->table_exists('channel_field_groups_fields'))
			{
				$rows = ee()->db->where('field_id', $old_field_id)
					->get('channel_field_groups_fields')
					->result_array();
			
				foreach ($rows as $row) {
					$data = array(
						'field_id' => $new_field_id,
						'group_id' => $row['group_id']
					);
					ee()->db->insert('channel_field_groups_fields', $data);
				}
			}
			
			if (ee()->db->table_exists('channels_channel_fields'))
			{
				$rows = ee()->db->where('field_id', $old_field_id)
					->get('channels_channel_fields')
					->result_array();
			
				foreach ($rows as $row) {
					$data = array(
						'field_id' => $new_field_id,
						'channel_id' => $row['channel_id']
					);
					ee()->db->insert('channels_channel_fields', $data);
				}
			}
		}
	}

}
// EOF