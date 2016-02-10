<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use PlayaMatrixImporter\Converters\PlayaConverter;
use PlayaMatrixImporter\Converters\MatrixConverter;

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
 * ExpressionEngine Matrix Importer Library
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Matrix_importer {

	/**
	 * Performs the full import of all Matrix channel fields into native Grid fields
	 *
	 * @return	array	Array of new Grid field IDs
	 */
	public function do_import()
	{
		ee()->load->library('pm_import_common');

		$matricies = $this->get_matrix_fields();
		$columns = $this->get_matrix_columns();

		// Load Grid package so we can get a list of Grid compatible fieldtypes
		ee()->load->add_package_path(PATH_FT.'grid/');

		ee()->load->library('grid_lib');
		$grid_fieldtypes = array_keys(ee()->grid_lib->get_grid_fieldtypes());

		ee()->load->remove_package_path(PATH_FT.'grid/');

		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');

		ee()->load->model('grid_model');

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
				'group_id'				=> $matrix['group_id'],
				'field_label'			=> $matrix['field_label'],
				'field_name'			=> ee()->pm_import_common->get_unique_field_name($matrix['field_name'], '_grid', $matrix['site_id']),
				'field_type'			=> 'grid',
				'field_instructions'	=> $matrix['field_instructions'],
				'field_required'		=> $matrix['field_required'],
				'field_search'			=> $matrix['field_search'],
				'field_order'			=> 0
			);

			// Hack to prevent errors from showing from Grid_lib when we create the field
			$_POST['grid']['cols'] = array();

			// Hack to prevent site ID mismatch error in API channel fields
			ee()->config->config['site_id'] = $new_grid_field['site_id'];

			$field_id = ee()->api_channel_fields->update_field($new_grid_field);

			// Reset the hack
			ee()->config->config['site_id'] = $original_site_id;

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

			// Fill Grid field with new data
			ee()->db->insert_batch('channel_grid_field_'.$field_id, $new_data);

			$matrix_to_grid_fields[$matrix['field_id']] = $field_id;
		}

		// Import Playa data for Grid columns
		$this->import_playa_data($matrix_to_grid_fields, $matrix_to_grid_cols);

		// For columns marked as searchable, copy their searchable text over
		// to the new Grid fields=
		$this->import_searchable_data($matrix_to_grid_fields);

		return array_values($matrix_to_grid_fields);
	}

	/**
	 * Gets us an array of Matrix fields
	 *
	 * @return	array	Database result array of Matrix fields
	 */
	private function get_matrix_fields()
	{
		return ee()->db->where('field_type', 'matrix')
			->get('channel_fields')
			->result_array();
	}

	/**
	 * Gets us an array of Matrix columns indexed and grouped by field ID
	 *
	 * @return	array	Database result array of Matrix fields
	 */
	private function get_matrix_columns()
	{
		$columns_query = ee()->db->where('var_id', NULL)
			->get('matrix_cols')->result_array();

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

		foreach ($matrix_field_ids as $field_id)
		{
			ee()->db->select('field_id_'.$field_id);
		}

		return ee()->db->select('entry_id')
			->get('channel_data')
			->result_array();
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
			$new_channel_data = array();

			foreach ($channel_data as $row)
			{
				$update = FALSE;
				$new_row = array();

				// Go over each field and map the field IDs to the new ones
				foreach ($row as $key => $value)
				{
					// Skip entry_id and any empty columns
					if ($key == 'entry_id' OR empty($value))
					{
						continue;
					}

					$matrix_field_id = str_replace('field_id_', '', $key);

					$new_row['field_id_'.$matrix_to_grid_fields[$matrix_field_id]] = $value;
				}

				// Any searchable data in this row? If so, add it
				if ( ! empty($new_row))
				{
					$new_row['entry_id'] = $row['entry_id'];
					$new_channel_data[] = $new_row;
				}
			}

			// Update entry data with searchable data, if we ahve any
			if ( ! empty($new_channel_data))
			{
				ee()->db->update_batch('channel_data', $new_channel_data, 'entry_id');
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

			ee()->db->insert_batch('relationships', $new_relationships);
		}
	}
}

// EOF
