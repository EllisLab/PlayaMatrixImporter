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
 * ExpressionEngine Playa Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class PlayaConverter
{
	/**
	 * Given an array of Playa field settings, returns a Relationships-compatible equivalent
	 *
	 * @param	array	Decoded and unserialized field_settings for a Playa field
	 * @return	array	Equivalent Relationships settings
	 */
	public static function convertSettings($settings)
	{
		// Defaults; this are defaults to playa fields because in some old cases, defaults
		// weren't saved, but we want to keep them if that's how the field was set up
		$relationship_settings = array(
			'channels'			=> array(),
			'categories'		=> array(),
			'authors'			=> array(),
			'statuses'			=> array(),
			'allow_multiple'	=> FALSE,
			'expired'			=> FALSE,
			'future'			=> TRUE,
			'order_field'		=> 'title',
			'order_dir'			=> 'asc',
			'limit'				=> 0,
		);

		foreach (array('channels', 'categories', 'authors', 'statuses') as $value)
		{
			// Categories are under the key 'cats' in Playa
			$key = ($value == 'categories') ? 'cats' : $value;

			if (isset($settings[$key]) && is_array($settings[$key]) && ! in_array('any', $settings[$key]))
			{
				$relationship_settings[$value] = $settings[$key];
			}
			else
			{
				$relationship_settings[$value] = array();
			}
		}

		// Channels may be under 'blogs' or 'channels'
		if (isset($settings['blogs']) && is_array($settings['blogs']) && ! in_array('any', $settings['blogs']))
		{
			$relationship_settings['channels'] = $settings['blogs'];
		}

		// Multi-relationship setting can be under any of these keys
		if ((isset($settings['multi']) && $settings['multi'] == 'y') OR
			(isset($settings['ui_mode']) && $settings['ui_mode'] != 'select'))
		{
			$relationship_settings['allow_multiple'] = TRUE;
		}

		if (isset($settings['expired']) && $settings['expired'] == 'y')
		{
			$relationship_settings['expired'] = TRUE;
		}

		if (isset($settings['future']) && $settings['future'] == 'n')
		{
			$relationship_settings['future'] = FALSE;
		}

		if (isset($settings['orderby']) && in_array($settings['orderby'], array('title', 'entry_date')))
		{
			$relationship_settings['order_field'] = $settings['orderby'];
		}

		if (isset($settings['sort']) && in_array($settings['sort'], array('ASC', 'DESC')))
		{
			$relationship_settings['order_dir'] = strtolower($settings['sort']);
		}

		if (isset($settings['limit']) && is_numeric($settings['limit']))
		{
			$relationship_settings['limit'] = $settings['limit'];
		}

		return $relationship_settings;
	}

	/**
	 * Given an array of Playa relationship data, translates it into a format compatible with Relationships
	 *
	 * @param	array	Database result array of Playa relationships
	 * @param	int		Field ID of Relationships field to create relationships for
	 * @return	array	Array of relationships ready to be batch-inserted into the relationships table
	 */
	public static function convertPlayaRelationships($playa_relationships, $new_field_id)
	{
		$relationships = array();

		foreach ($playa_relationships as $playa_rel)
		{
			$relationship = array(
				'parent_id'	=> $playa_rel['parent_entry_id'],
				'field_id'	=> $new_field_id,
				'child_id'	=> $playa_rel['child_entry_id'],
				'order'		=> $playa_rel['rel_order']
			);

			// Bring over Publisher data if present, too
			if (isset($playa_rel['publisher_lang_id']) && isset($playa_rel['publisher_status']))
			{
				$relationship['publisher_lang_id'] = $playa_rel['publisher_lang_id'];
				$relationship['publisher_status'] = $playa_rel['publisher_status'];
			}

			$relationships[] = $relationship;
		}

		return $relationships;
	}

	/**
	 * Given an array of Playa relationship data for Matrix fields, translates it into a format
	 * compatible with Relationships and Grid
	 *
	 * @param	array	Database result array of Playa relationships
	 * @param	array	Assocative array of Matrix field IDs to Grid field IDs
	 * @param	array	Assocative array of Matrix column IDs to Grid column IDs
	 * @return	array	Array of relationships ready to be batch-inserted into the relationships table
	 */
	public static function convertPlayaRelationshipsForGrid($playa_relationships, $matrix_to_grid_fields, $matrix_to_grid_cols)
	{
		$relationships = array();

		foreach ($playa_relationships as $playa_rel)
		{
			$relationship = array(
				'parent_id'			=> $playa_rel['parent_entry_id'],
				'field_id'			=> $matrix_to_grid_cols[$playa_rel['parent_col_id']],
				'child_id'			=> $playa_rel['child_entry_id'],
				'order'				=> $playa_rel['rel_order'],
				'grid_field_id'		=> $matrix_to_grid_fields[$playa_rel['parent_field_id']],
				'grid_col_id'		=> $matrix_to_grid_cols[$playa_rel['parent_col_id']],
				'grid_row_id'		=> $playa_rel['parent_row_id'],
			);

			// Bring over Publisher data if present, too
			if (isset($playa_rel['publisher_lang_id']) && isset($playa_rel['publisher_status']))
			{
				$relationship['publisher_lang_id'] = $playa_rel['publisher_lang_id'];
				$relationship['publisher_status'] = $playa_rel['publisher_status'];
			}

			$relationships[] = $relationship;
		}

		return $relationships;
	}

	/**
	 * When Publisher is installed, records taken straight from the Playa table are intermixed
	 * with draft entries and other languages; those rows will go into the publisher_relationships
	 * table, but the only rows that should go into the relationships table are ones that are
	 * a) open and b) belong to the default language
	 *
	 * @param	array	Array of relationships formatted to be batch-inserted into the publisher_relationships table
	 * @param	array	Default Publisher language ID
	 * @return	array	Array of relationships ready to be batch-inserted into the relationships table
	 */
	public static function filterPublisherRelationships(array $relationships, $default_lang_id)
	{
		foreach ($relationships as $key => $value)
		{
			// Only keep open items and items in the default language
			if ($value['publisher_lang_id'] != $default_lang_id OR $value['publisher_status'] != 'open')
			{
				unset($relationships[$key]);
			}

			// These columns aren't in the relationships table
			unset($relationships[$key]['publisher_lang_id']);
			unset($relationships[$key]['publisher_status']);
		}

		return $relationships;
	}
}
// EOF