<?php

namespace PlayaMatrixImporter\Converters;

use PlayaMatrixImporter\Converters\FileCellConverter;
use PlayaMatrixImporter\Converters\OptionsCellConverter;
use PlayaMatrixImporter\Converters\PlayaConverter;
use PlayaMatrixImporter\Converters\TextCellConverter;

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
 * ExpressionEngine Matrix Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://packettide.com
 */

class MatrixConverter
{
	/**
	 * Given an array of Matrix field settings, returns an EE-compatible equivalent for Grid
	 *
	 * @param	array	Decoded and unserialized field_settings for a Matrix field
	 * @return	array	Equivalent native Grid field settings
	 */
	public static function convertSettings($settings)
	{
		$defaults = array(
			'grid_min_rows'	=> 0,
			'grid_max_rows'	=> ''
		);

		foreach (array(
			'min_rows' => 'grid_min_rows',
			'max_rows' => 'grid_max_rows') as $cell_key => $field_key)
		{
			if (isset($settings[$cell_key]))
			{
				$defaults[$field_key] = $settings[$cell_key];
			}
		}

		return $defaults;
	}

	/**
	 * Given a Matrix column, creates a the Grid column-equivalent
	 *
	 * @param	array	Database result array of a single Matrix column
	 * @param	array		Array of Grid-compatible fieldtypes' short names
	 * @param	int		Field ID of new Grid field to create this column for
	 * @return	array	New Grid column ready to send to save_col_settings
	 */
	public static function convertMatrixColumns($columns, $grid_fieldtypes, $field_id)
	{
		$grid_columns = array();
		foreach ($columns as $matrix_column)
		{
			// Default width to zero
			if (empty($matrix_column['col_width']))
			{
				$matrix_column['col_width'] = 0;
			}

			$settings = unserialize(base64_decode($matrix_column['col_settings']));
			$column_type = self::mapColumnType($matrix_column['col_type'], $settings, $grid_fieldtypes);
			$new_settings = self::mapColumnSettings($column_type, $settings);

			$grid_columns[$matrix_column['col_id']] = array(
				'field_id'			=> $field_id,
				'content_type'		=> 'channel',
				'col_order'			=> $matrix_column['col_order'],
				'col_type'			=> $column_type,
				// Matrix columns can be saved without a column name or label, make sure our columns have something
				'col_label'			=> $matrix_column['col_label'] ?: 'Column '.$matrix_column['col_id'],
				'col_name'			=> $matrix_column['col_name'] ?: 'col_id_'.$matrix_column['col_id'],
				'col_instructions'	=> $matrix_column['col_instructions'],
				'col_required'		=> $matrix_column['col_required'],
				'col_search'		=> $matrix_column['col_search'],
				'col_width'			=> str_replace('%', '', $matrix_column['col_width']),
				'col_settings'		=> json_encode($new_settings)
			);
		}

		return $grid_columns;
	}

	/**
	 * Given a Matrix celltype name, returns the the Grid-compatible fieldtype name
	 *
	 * @param	string		Celltype name, e.g. 'text', 'playa'
	 * @param	settings	Decoded settings for the Matrix column, needed sometimes
	 *	to map to a different fieldtype based on settings
	 * @param	array		Array of Grid-compatible fieldtypes' short names
	 * @return	string		EE-equivalent, Grid-compatible fieldtype name
	 */
	public static function mapColumnType($from_cell_type, $settings, $grid_fieldtypes = array())
	{
		$col_type_mapping = array(
			'date'						=> 'date',
			'fieldpack_checkboxes'		=> 'checkboxes',
			'fieldpack_dropdown'		=> 'select',
			'fieldpack_list'			=> 'textarea',
			'fieldpack_multiselect'		=> 'multi_select',
			'fieldpack_pill'			=> 'radio',
			'fieldpack_radio_buttons'	=> 'radio',
			'fieldpack_switch'			=> 'radio',
			'file'						=> 'file',
			'number'					=> 'text',
			'playa'						=> 'relationship',
			'rte'						=> 'rte',
			'text'						=> 'text'
		);

		// Make sure Text celltypes with the multiline option set to 'y'
		// get set to be a Textarea
		if ($from_cell_type == 'text' &&
			isset($settings['multiline']) &&
			$settings['multiline'] == 'y')
		{
			return 'textarea';
		}

		if (isset($col_type_mapping[$from_cell_type]))
		{
			return $col_type_mapping[$from_cell_type];
		}

		// Is the source celltype Grid compatible? If so, let's try to keep if
		if (in_array($from_cell_type, $grid_fieldtypes))
		{
			return $from_cell_type;
		}

		// Fall back to text for any unrecognized cell types
		return 'text';
	}

	/**
	 * Given decoded Matrix column settings, converts those settings to be
	 * compatible to the specified native EE fieldtype
	 *
	 * @param	string		Target fieldtype's name, e.g. 'text', 'relationship'
	 * @param	settings	Decoded settings for the Matrix column
	 * @return	string		EE-equivalent fieldtype settings for specified fieldtype
	 */
	public static function mapColumnSettings($to_cell_type, $settings)
	{
		switch ($to_cell_type) {
			case 'date':
				// Matrix has no date field settings, we only have one
				return array('localize' => TRUE);
				break;

			case 'checkboxes':
			case 'radio':
			case 'select':
			case 'multi_select':
				return OptionsCellConverter::convertSettings($settings);
				break;

			case 'relationship':
				return PlayaConverter::convertSettings($settings);
				break;

			case 'file':
				return FileCellConverter::convertSettings($settings);
				break;

			case 'textarea':
				return TextareaCellConverter::convertSettings($settings);
				break;

			case 'rte':
				// Existing settings should be good except we have one thing to add
				return array_merge(array('field_text_direction' => 'ltr'), $settings);
				break;

			case 'text':
				return TextCellConverter::convertSettings($settings);

			default:
				return $settings;
				break;
		}

		return array();
	}

	/**
	 * Given an array of Matrix data from a single field, maps it to its
	 * correllating Grid field, column IDs and all
	 *
	 * @param	array	Database result array of Matrix data for a single field
	 * @param	array	Associative array of Matrix column IDs to their respective
	 *	Grid column IDs
	 * @param	array	Array of original Matrix columns from Matrix importer's get_matrix_columns
	 * @return	array	New Grid field data ready to be inserted into the field's table
	 */
	public static function convertMatrixData($matrix_data, $matrix_to_grid_cols, $columns)
	{
		// Loop over the old data and map it to our new table and column IDs
		$grid_data = array();
		foreach ($matrix_data as $row)
		{
			$new_grid_row = array(
				'row_id'	=> $row['row_id'], // We'll just keep the row IDs the same for folks who rely on them in templates
				'entry_id'	=> $row['entry_id'],
				'row_order'	=> $row['row_order']
			);

			foreach ($matrix_to_grid_cols as $matrix_col_id => $grid_col_id)
			{
				// Some columns may have been omitted, like Playa columns
				if (array_key_exists('col_id_'.$matrix_col_id, $row))
				{
					// Decode original column settings in case we need to dig into
					// column options
					$col_settings = unserialize(base64_decode($columns[$matrix_col_id]['col_settings']));
					$row_data = $row['col_id_'.$matrix_col_id];

					// Options cells need their data converted into a different format
					// in order to be read by native fieldtypes
					if (in_array(
						self::mapColumnType($columns[$matrix_col_id]['col_type'], $col_settings),
						array('checkboxes', 'radio', 'select', 'multi_select')
					))
					{
						$row_data = OptionsCellConverter::convertData($row_data, $col_settings);
					}

					$new_grid_row['col_id_'.$grid_col_id] = $row_data;
				}
			}

			// Bring over Publisher data if present, too
			if (isset($row['publisher_lang_id']) && isset($row['publisher_status']))
			{
				$new_grid_row['publisher_lang_id'] = $row['publisher_lang_id'];
				$new_grid_row['publisher_status'] = $row['publisher_status'];
			}

			$grid_data[] = $new_grid_row;
		}

		return $grid_data;
	}
}