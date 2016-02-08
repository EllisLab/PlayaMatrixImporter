<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use PlayaMatrixImporter\Converters\PlayaConverter;

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
 * ExpressionEngine Playa Importer Library
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Playa_importer {

	/**
	 * Performs the full import of all regular (non-Matrix) Playa channel fields
	 * into native Relationships fields
	 *
	 * @return	array	Array of new Playa field IDs
	 */
	public function do_import()
	{
		ee()->load->library('pm_import_common');
		$playas = $this->get_playa_fields();
		$playa_relationships = ee()->pm_import_common->get_playa_relationships();

		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');

		$new_field_ids = array();

		$original_site_id = ee()->config->item('site_id');

		foreach ($playas as $playa)
		{
			// Let's be explicit about what's making up this new field
			$new_relationship_field = array(
				'site_id'				=> $playa['site_id'],
				'group_id'				=> $playa['group_id'],
				'field_label'			=> $playa['field_label'],
				'field_name'			=> ee()->pm_import_common->get_unique_field_name($playa['field_name'], '_relate', $playa['site_id']),
				'field_type'			=> 'relationship',
				'field_instructions'	=> $playa['field_instructions'],
				'field_required'		=> $playa['field_required'],
				'field_search'			=> $playa['field_search'],
				'field_order'			=> 0
			);

			// Hack to prevent site ID mismatch error in API channel fields
			ee()->config->config['site_id'] = $new_relationship_field['site_id'];

			$field_id = ee()->api_channel_fields->update_field($new_relationship_field);

			ee()->config->config['site_id'] = $original_site_id;

			$new_field_settings = PlayaConverter::convertSettings(unserialize(base64_decode($playa['field_settings'])));
			$new_relationships = PlayaConverter::convertPlayaRelationships($playa_relationships[$playa['field_id']], $field_id);

			// Set new field settings from translated Playa settings
			ee()->db->set('field_settings', base64_encode(serialize($new_field_settings)))
				->where('field_id', $field_id)
				->update('channel_fields');

			// Finally, import Playa relationships
			ee()->db->insert_batch('relationships', $new_relationships);

			$new_field_ids[] = $field_id;
		}

		return $new_field_ids;
	}

	/**
	 * Gets us an array of regular Playa channel fields
	 *
	 * @return	array	Database result array of Playa fields
	 */
	public function get_playa_fields()
	{
		return ee()->db->where('field_type', 'playa')
			->get('channel_fields')
			->result_array();
	}
}

// EOF
