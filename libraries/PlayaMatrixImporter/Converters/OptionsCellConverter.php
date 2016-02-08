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
 * ExpressionEngine Options Cell Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class OptionsCellConverter {

	/**
	 * Given an array of field settings an options-type field, returns an EE-compatible equivalent
	 *
	 * @param	array	Decoded and unserialized field_settings for an options field
	 * @return	array	Equivalent native field settings fit for any options field
	 */
	public static function convertSettings($settings)
	{
		$defaults = array(
			'field_pre_populate'	=> 'n',
			'field_fmt'				=> 'none',
			'field_list_items'		=> array(),
		);

		if (isset($settings['options']) && is_array($settings['options']))
		{
			$defaults['field_list_items'] = implode("\n", array_values($settings['options']));
		}
		// Switch celltype
		else if (isset($settings['off_label']) && isset($settings['on_label']))
		{
			$defaults['field_list_items'] = implode("\n", array($settings['off_label'], $settings['on_label']));
		}

		return $defaults;
	}

	/**
	 * Converts data from a Matrix options celltype into a format readable by native
	 * option fieldtypes
	 *
	 * @param	mixed	Raw data value from Matrix column
	 * @param	array	Original column settings for Matrix column
	 * @return	mixed	Raw data value to store in new Grid field
	 */
	public static function convertData($data, $settings)
	{
		// Empty field? Do nothing
		if (empty($data))
		{
			return '';
		}

		// Some celltypes allow for key => value options to be stored and they
		// subsequently store the keys in the data table; EE only works with
		// values so we need to map those keys to their respective values and
		// store those instead
		if (isset($settings['options']))
		{
			// Matrix data is stored separated by linebreak
			$data = explode("\n", $data);

			$new_data = array();

			// For each option, find its corresponding value
			foreach ($data as $option)
			{
				if (isset($settings['options'][$option]))
				{
					$new_data[] = $settings['options'][$option];
				}
			}

			// EE data is stored pipe-delimited
			return implode('|', $new_data);
		}

		// For Switch celltypes
		if (isset($settings['off_label']))
		{
			if ($data == $settings['off_val'])
			{
				return $settings['off_label'];
			}

			if ($data == $settings['on_val'])
			{
				return $settings['on_label'];
			}
		}

		// Hopefully should never get here in practice
		return '';
	}
}