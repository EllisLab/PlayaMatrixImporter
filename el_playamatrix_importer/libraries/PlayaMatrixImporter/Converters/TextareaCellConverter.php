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
 * ExpressionEngine Textarea Cell Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class TextareaCellConverter {

	/**
	 * Given an array of Textarea field settings, returns an EE-compatible equivalent
	 *
	 * @param	array	Decoded and unserialized field_settings for a Textarea field
	 * @return	array	Equivalent native textarea field settings
	 */
	public static function convertSettings($settings)
	{
		$defaults = array(
			'field_ta_rows'				=> 6,
			'field_fmt'					=> 'none',
			'field_text_direction'		=> 'ltr',
			'show_formatting_buttons'	=> FALSE,
		);

		foreach (array(
			'rows' => 'field_ta_rows',
			'fmt' => 'field_fmt',
			'dir' => 'field_text_direction') as $cell_key => $field_key)
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