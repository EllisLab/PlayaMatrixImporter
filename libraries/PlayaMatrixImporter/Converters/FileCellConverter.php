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
 * ExpressionEngine File Cell Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class FileCellConverter {

	/**
	 * Given an array of File field settings, returns an EE-compatible equivalent
	 *
	 * @param	array	Decoded and unserialized field_settings for a File field
	 * @return	array	Equivalent native file field settings
	 */
	public static function convertSettings($settings)
	{
		$defaults = array(
			'field_content_type'	=> 'all',
			'allowed_directories'	=> 'all',
			'show_existing'			=> 'y',
			'num_existing'			=> 50,
			'field_fmt'				=> 'none'
		);

		foreach (array(
			'directory' => 'allowed_directories',
			'content_type' => 'field_content_type') as $cell_key => $field_key)
		{
			if (isset($settings[$cell_key]))
			{
				$defaults[$field_key] = $settings[$cell_key];
			}
		}

		return $defaults;
	}
}
// EOF