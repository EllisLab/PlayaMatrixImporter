<?php

namespace PlayaMatrixImporter\Converters;

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
 * ExpressionEngine Assets Cell Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class AssetsCellConverter {

	/**
	 * Copoes Matrix Assets cell data in the assets_selections table to be used in Grid
	 *
	 * @param	array	Database result array of Assets data
	 * @param	array	Assocative array of Matrix field IDs to Grid field IDs
	 * @param	array	Assocative array of Matrix column IDs to Grid column IDs
	 * @return	array	Array of Assets data ready to be batch-inserted into the assets_selections table
	 */
	public static function convertData($assets_selections, $matrix_to_grid_fields, $matrix_to_grid_cols)
	{
		foreach ($assets_selections as &$row)
		{
			$row['field_id'] = $matrix_to_grid_fields[$row['field_id']];
			$row['col_id'] = $matrix_to_grid_cols[$row['col_id']];
			$row['content_type'] = 'grid';
		}

		return $assets_selections;
	}
}