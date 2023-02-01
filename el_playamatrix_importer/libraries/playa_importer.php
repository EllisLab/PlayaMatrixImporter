<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use PlayaMatrixImporter\Converters\PlayaConverter;

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
 * ExpressionEngine Playa Importer Library
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://packettide.com
 */

class Playa_importer {

	private $EE3 = FALSE;
	private $EE4 = FALSE;

	/**
	 * Performs the full import of all regular (non-Matrix) Playa channel fields
	 * into native Relationships fields
	 *
	 * @return	array	Array of new Playa field IDs
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
		$playas = $this->get_playa_fields($fields);
		$playa_relationships = ee()->pm_import_common->get_playa_relationships();


		ee()->load->library('api');
		ee()->load->model('addons_model');

				
		if ($this->EE3)
		{
			ee()->legacy_api->instantiate('channel_fields');
		}
		else 
		{
			ee()->api->instantiate('channel_fields');
		}		

		$new_field_ids = array();

		foreach ($playas as $playa)
		{
			// Let's be explicit about what's making up this new field
			$new_relationship_field = array(
				'site_id'				=> $playa['site_id'],
				'group_id'				=> (isset($playa['group_id']) ? $playa['group_id'] : 0),
				'field_label'			=> $playa['field_label'],
				'field_name'			=> ee()->pm_import_common->get_unique_field_name($playa['field_name'], '_relate', $playa['site_id']),
				'field_type'			=> 'relationship',
				'field_instructions'	=> $playa['field_instructions'],
				'field_required'		=> $playa['field_required'],
				'field_search'			=> $playa['field_search'],
				'field_order'			=> 0
			);
			
			if ($this->EE4) 
			{
				unset($new_relationship_field['group_id']);
				
				$field = ee('Model')->make('ChannelField');
				$field->site_id     = $new_relationship_field['site_id'];
				$field->field_name  = $new_relationship_field['field_name'];
				$field->field_type  = $new_relationship_field['field_type'];

				$field->set($new_relationship_field);
				$field->save();

				$field_id = $field->field_id;
			}
			else 
			{

				$original_site_id = ee()->config->item('site_id');

				// Hack to prevent site ID mismatch error in API channel fields
				ee()->config->config['site_id'] = $new_relationship_field['site_id'];

				$field_id = ee()->api_channel_fields->update_field($new_relationship_field);

				ee()->config->config['site_id'] = $original_site_id;
			}
			

			$new_field_settings = PlayaConverter::convertSettings(unserialize(base64_decode($playa['field_settings'])));

			$new_relationships = array();
			if (isset($playa_relationships[$playa['field_id']]))
			{
				$new_relationships = PlayaConverter::convertPlayaRelationships($playa_relationships[$playa['field_id']], $field_id);
			}

			// Set new field settings from translated Playa settings
			ee()->db->set('field_settings', base64_encode(serialize($new_field_settings)))
				->where('field_id', $field_id)
				->update('channel_fields');

			if ( ! empty($new_relationships))
			{
				if (ee()->addons_model->module_installed('publisher') && ee()->db->table_exists('publisher_relationships'))
				{
					ee()->db->insert_batch('publisher_relationships', $new_relationships);

					$new_relationships = PlayaConverter::filterPublisherRelationships(
						$new_relationships,
						ee()->config->item('publisher_default_language_id') ?: 1
					);
				}

				// Finally, import Playa relationships
				ee()->db->insert_batch('relationships', $new_relationships);
			}

			$new_field_ids[$playa['field_id']] = $field_id;
		}

		return $new_field_ids;
	}

	/**
	 * Gets us an array of regular Playa channel fields
	 *
	 * @return	array	Database result array of Playa fields
	 */
	public function get_playa_fields($fields)
	{
		return ee()->db->where('field_type', 'playa')
			->where_in('field_id', $fields)
			->get('channel_fields')
			->result_array();
	}
}

// EOF
