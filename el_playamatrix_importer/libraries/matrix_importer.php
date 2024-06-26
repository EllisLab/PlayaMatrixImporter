<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use PlayaMatrixImporter\Converters\PlayaConverter;
use PlayaMatrixImporter\Converters\MatrixConverter;
use PlayaMatrixImporter\Converters\AssetsCellConverter;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://packettide.com
 * @since		Version 2.10.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Matrix Importer Library
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://packettide.com
 */

class Matrix_importer {

	private $EE3 = FALSE;
	private $EE4 = FALSE;

	/**
	 * Performs the full import of all Matrix channel fields into native Grid fields
	 *
	 * @return	array	Array of new Grid field IDs
	 */
	public function do_import($fields)
	{
		if (defined('APP_VER') && version_compare(APP_VER, '3.0.0', '>='))
		{
			$this->EE3 = TRUE;
		}
		if (defined('APP_VER') && version_compare(APP_VER, '4.0.0', '>='))
		{
			$this->EE4 = TRUE;
		}
		
		ee()->load->library('pm_import_common');

		$matricies = $this->get_matrix_fields($fields);
		$columns = $this->get_matrix_columns($fields);


		ee()->load->library('api');
		ee()->load->model('grid_model');
		ee()->load->model('addons_model');
		
		
		if ($this->EE3)
		{
			ee()->legacy_api->instantiate('channel_fields');
		}
		else 
		{
			ee()->api->instantiate('channel_fields');
		}

		$ft_api = ee()->api_channel_fields;
		$fieldtypes = $ft_api->fetch_installed_fieldtypes();

		// Load Grid package so we can get a list of Grid compatible fieldtypes
		ee()->load->add_package_path(PATH_FT.'grid/');

		ee()->load->library('grid_lib');

		$grid_fieldtypes = array_keys(ee()->grid_lib->get_grid_fieldtypes());

		ee()->load->remove_package_path(PATH_FT.'grid/');
		

		$original_site_id = ee()->config->item('site_id');

		// We'll keep track of the mapping of Matrix fields and columns to their
		// corresponding new Grid fields and columns here
		$matrix_to_grid_fields = array();
		$matrix_to_grid_cols = array();


		foreach ($matricies as $matrix)
		{
			// Let's be explicit about what's making up this new field
			$new_grid_field = array(
				'site_id'				=> $matrix['site_id'],
				'group_id'				=> (isset($matrix['group_id']) ? $matrix['group_id'] : 0),
				'field_label'			=> $matrix['field_label'],
				'field_name'			=> ee()->pm_import_common->get_unique_field_name($matrix['field_name'], '_grid', $matrix['site_id']),
				'field_type'			=> 'grid',
				'field_instructions'	=> $matrix['field_instructions'],
				'field_required'		=> $matrix['field_required'],
				'field_search'			=> $matrix['field_search'],
				'field_order'			=> 0,
				'field_list_items'		=> ''
			);
			

			// Hack to prevent errors from showing from Grid_lib when we create the field
			$_POST['grid']['cols'] = array();


			if ($this->EE4)
			{
				unset($new_grid_field['group_id']);
				
				$field = ee('Model')->make('ChannelField');
				$field->site_id     = $new_grid_field['site_id'];
				$field->field_name  = $new_grid_field['field_name'];
				$field->field_type  = $new_grid_field['field_type'];

				$field->set($new_grid_field);
				$field->save();

				$field_id = $field->field_id;
			}
			else 
			{

				// Hack to prevent site ID mismatch error in API channel fields
				ee()->config->config['site_id'] = $new_grid_field['site_id'];

				// Create field - could use legacy/models/grid_model.php or Addons/grid/Grid_lib.php to create non-legacy fields
				$field_id = ee()->api_channel_fields->update_field($new_grid_field);

				// Reset the hack
				ee()->config->config['site_id'] = $original_site_id;
			}
			

			$new_field_settings = MatrixConverter::convertSettings(unserialize(base64_decode($matrix['field_settings'])));

			// Set new field settings from converted Matrix settings
			ee()->db->set('field_settings', base64_encode(serialize($new_field_settings)))
				->where('field_id', $field_id)
				->update('channel_fields');

			$new_columns = MatrixConverter::convertMatrixColumns($columns[$matrix['field_id']], $grid_fieldtypes, $field_id);

			// Create new columns and gather new column IDs to map to old columns
			$new_column_ids = array();
			$playa_columns = array();
			foreach ($new_columns as $matrix_col_id => $column)
			{
				$col_id = ee()->grid_model->save_col_settings($column);

				// Create mapping from old columns to new
				$matrix_to_grid_cols[$matrix_col_id] = $col_id;
				$new_column_ids[$matrix_col_id] = $col_id;

				// Collect Playa cell IDs so we know not to import the data in those,
				// we don't need to store anything about relationships in the Grid
				// field table
				if ($column['col_type'] == 'relationship')
				{
					$playa_columns[] = $matrix_col_id;
				}
			}

			$matrix_data = $this->get_matrix_data($new_column_ids, $matrix['field_id'], $playa_columns);
			$new_data = MatrixConverter::convertMatrixData($matrix_data, $new_column_ids, $columns[$matrix['field_id']]);

			// Add Publisher fields if installed
			if (ee()->addons_model->module_installed('publisher'))
			{
				ee()->pm_import_common->add_publisher_columns('channel_grid_field_'.$field_id);
			}

			// Fill Grid field with new data
			if ( ! empty($new_data))
			{
				ee()->db->insert_batch('channel_grid_field_'.$field_id, $new_data);
			}

			$matrix_to_grid_fields[$matrix['field_id']] = $field_id;
		}

		// Import Playa data for Grid columns
		$this->import_playa_data($matrix_to_grid_fields, $matrix_to_grid_cols);

		// Import Assets data
		$this->import_assets_data($matrix_to_grid_fields, $matrix_to_grid_cols);

		// For columns marked as searchable, copy their searchable text over
		// to the new Grid fields=
		$this->import_searchable_data($matrix_to_grid_fields);
		

		//return array_values($matrix_to_grid_fields);
		return $matrix_to_grid_fields;
	}

	/**
	 * Gets us an array of Matrix fields
	 *
	 * @return	array	Database result array of Matrix fields
	 */
	private function get_matrix_fields($fields)
	{
		return ee()->db->where('field_type', 'matrix')
			->where_in('field_id', $fields)
			->get('channel_fields')
			->result_array();
	}

	/**
	 * Gets us an array of Matrix columns indexed and grouped by field ID
	 *
	 * @return	array	Database result array of Matrix fields
	 */
	private function get_matrix_columns($fields)
	{
		// Skip Low vars for now
		if (ee()->db->field_exists('var_id', 'matrix_cols'))
		{
			ee()->db->where('var_id', NULL)
				->or_where('var_id', 0);
		}
		$columns_query = ee()->db->where_in('field_id', $fields)->get('matrix_cols')->result_array();

		$columns = array();
		foreach ($columns_query as $column)
		{
			$columns[$column['field_id']][$column['col_id']] = $column;
		}

		return $columns;
	}

	/**
	 * Get Matrix field data for a single field
	 *
	 * @param	array	Assocative array of Matrix column IDs to Grid column IDs
	 * @param	int		Field ID of Matrix field to gather data for
	 * @param	array	Array of Playa column IDs that may be present so we can skip them
	 * @return	array	Database result array of Matrix field data
	 */
	private function get_matrix_data($matrix_to_grid_cols, $matrix_field_id, $playa_columns)
	{
		// Is Publisher installed? We'll need to bring that data over as well
		if (ee()->addons_model->module_installed('publisher') && ee()->db->field_exists('publisher_lang_id', 'matrix_data'))
		{
			ee()->db->select('publisher_lang_id, publisher_status');
		}

		// Let's get the data for these old columns to transfer over to our new columns
		foreach ($matrix_to_grid_cols as $matrix_col_id => $grid_col_id)
		{
			// Skip Playa column data, we don't need it and it won't fit into our
			// Relationships data cells
			if ( ! in_array($matrix_col_id, $playa_columns))
			{
				ee()->db->select('col_id_'.$matrix_col_id);
			}
		}

		return ee()->db->select('row_id, entry_id, row_order')
			->where('field_id', $matrix_field_id)
			->get('matrix_data')
			->result_array();
	}

	/**
	 * Columns marked as searchable have their data duplicated and stored in the
	 * channel_data table; this method lets us get that data
	 *
	 * @param	array	Array of Matrix field IDs to get data for
	 * @return	array	Database result array of searchable Matrix field data
	 */
	private function get_matrix_channel_data($matrix_field_ids)
	{
		if (empty($matrix_field_ids))
		{
			return FALSE;
		}

		$data = array();

		foreach ($matrix_field_ids as $field_id)
		{
			$table = 'channel_data';
			
			ee()->db->select('field_id_'.$field_id);
			if (ee()->db->table_exists('channel_data_field_'.$field_id))
			{
				$table = 'channel_data_field_'.$field_id;
			}
			
			$result = ee()->db->select('entry_id')
				->get($table)
				->result_array();
				
			$data = array_merge($data, $result);
		}

		return $data;
	}
	
	
	/**
	 * Copies over field groups from Matrix fields into their new Grid fields'
	 *
	 * @param	array	Associative array of Matrix field IDs to their corresponding Grid field IDs
	 * @return	void
	 */
	private function import_field_groups($matrix_to_grid_fields)
	{
		foreach ($matrix_to_grid_fields as $matrix_field_id => $grid_field_id) 
		{
			if (ee()->db->table_exists('channel_field_groups_fields'))
			{
				$rows = ee()->db->where('field_id', $matrix_field_id)
					->get('channel_field_groups_fields')
					->result_array();
			
				foreach ($rows as $row) {
					$data = array(
						'field_id' => $grid_field_id,
						'group_id' => $row['group_id']
					);
					ee()->db->insert('channel_field_groups_fields', $data);
				}
			}
			
			if (ee()->db->table_exists('channels_channel_fields'))
			{
				$rows = ee()->db->where('field_id', $matrix_field_id)
					->get('channels_channel_fields')
					->result_array();
			
				foreach ($rows as $row) {
					$data = array(
						'field_id' => $grid_field_id,
						'channel_id' => $row['channel_id']
					);
					ee()->db->insert('channels_channel_fields', $data);
				}
			}
		}
	}
	

	/**
	 * Copys over searchable data from Matrix fields into their new Grid fields'
	 * column in the channel_data table
	 *
	 * @param	array	Associative array of Matrix field IDs to their corresponding Grid field IDs
	 * @return	void
	 */
	private function import_searchable_data($matrix_to_grid_fields)
	{
		if ($channel_data = $this->get_matrix_channel_data(array_keys($matrix_to_grid_fields)))
		{
			foreach ($channel_data as $row)
			{
				// Go over each field and map the field IDs to the new ones
				foreach ($row as $key => $value)
				{
					// Skip entry_id and any empty columns
					if ($key == 'entry_id' OR empty($value))
					{
						continue;
					}

					$matrix_field_id = str_replace('field_id_', '', $key);

					$field_id = $matrix_to_grid_fields[$matrix_field_id];

					$data = array();

					$data['field_id_'.$field_id] = $value;
					$data['field_ft_'.$field_id] = 'none';
					$where['entry_id'] = $row['entry_id'];

					/* 
					// cannot use table_exists directly after creating it - returns false
					$table = 'channel_data';
					if (ee()->db->table_exists('channel_data_field_'.$field_id))
					{
						$table = 'channel_data_field_'.$field_id;
					}
					*/

					$table = 'channel_data';
					if ($this->EE4) 
					{
						$table = 'channel_data_field_'.$field_id;
					}

					ee()->db->update($table, $data, $where);
					
				}
			}
		}
	}

	/**
	 * Import Playa data from Matrix columns
	 *
	 * @param	array	Assocative array of Matrix field IDs to Grid field IDs
	 * @param	array	Assocative array of Matrix column IDs to Grid column IDs
	 * @return	void
	 */
	private function import_playa_data($matrix_to_grid_fields, $matrix_to_grid_cols)
	{
		if ( ! ee()->db->table_exists('playa_relationships'))
		{
			return;
		}

		$playa_relationships = ee()->db->where('parent_col_id IS NOT NULL')
			->get('playa_relationships')
			->result_array();

		if (count($playa_relationships))
		{
			$new_relationships = PlayaConverter::convertPlayaRelationshipsForGrid(
				$playa_relationships,
				$matrix_to_grid_fields,
				$matrix_to_grid_cols
			);

			if (ee()->addons_model->module_installed('publisher') && ee()->db->table_exists('publisher_relationships'))
			{
				ee()->db->insert_batch('publisher_relationships', $new_relationships);

				$new_relationships = PlayaConverter::filterPublisherRelationships(
					$new_relationships,
					ee()->config->item('publisher_default_language_id') ?: 1
				);
			}

			// Data didn't make it out of the converter? It's been orphaned
			if (empty($new_relationships))
			{
				return;
			}

			ee()->db->insert_batch('relationships', $new_relationships);
		}
	}

	/**
	 * Import Assets data from assets_selections table
	 *
	 * @param	array	Assocative array of Matrix field IDs to Grid field IDs
	 * @param	array	Assocative array of Matrix column IDs to Grid column IDs
	 * @return	void
	 */
	private function import_assets_data($matrix_to_grid_fields, $matrix_to_grid_cols)
	{
		if ( ! ee()->db->table_exists('assets_selections'))
		{
			return;
		}

		$assets_selections = ee()->db->where('content_type', 'matrix')
			->get('assets_selections')
			->result_array();

		if (count($assets_selections))
		{
			$new_assets_selections = AssetsCellConverter::convertData(
				$assets_selections,
				$matrix_to_grid_fields,
				$matrix_to_grid_cols
			);

			// Data didn't make it out of the converter? It's been orphaned
			if (empty($new_assets_selections))
			{
				return;
			}

			ee()->db->insert_batch('assets_selections', $new_assets_selections);
		}
	}
}

// EOF
