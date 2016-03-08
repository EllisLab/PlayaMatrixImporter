<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Playa & Matrix Importer Common Library
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Pm_import_common {

	// Field names cache
	private $field_names = array();

	/**
	 * When we create new Grid or Relationship fields, we want to make sure the
	 * name we choose is unqiue to the field's site; if it's not, we'll keep
	 * adding numbers on the end until it is unique
	 *
	 * @param	string	Field name we want to make sure is unique
	 * @return	string	A field name that is definitely unique
	 */
	public function get_unique_field_name($field_name, $suffix, $site_id)
	{
		if ( ! count($this->field_names))
		{
			$field_names_query = ee()->db->select('site_id, field_name')->get('channel_fields')->result_array();

			// Group and index field names by site ID
			foreach ($field_names_query as $row)
			{
				$this->field_names[$row['site_id']][] = $row['field_name'];
			}
		}

		// Make sure proposed name isn't longer than allowed
		if (strlen($field_name.$suffix) > 32)
		{
			$field_name = substr($field_name, 0, 32 - strlen($suffix));
		}

		$field_name = $field_name.$suffix;

		// Check to see if the proposed field name exists, and append numbers
		// until it is unique
		$i = 1;
		while (in_array($field_name, $this->field_names[$site_id]))
		{
			$field_name = substr($field_name, 0, strlen($field_name) - strlen($i));
			$field_name .= $i++;
		}

		// Cache the field name we settled on
		$this->field_names[] = $field_name;

		return $field_name;
	}

	/**
	 * Gets us an array of existing relationship data indexed and grouped by field ID
	 *
	 * @return	array	Playa relationships indexed and grouped by field ID
	 */
	public function get_playa_relationships()
	{
		ee()->db->where('parent_col_id', NULL);

		$playa_relationships_query = ee()->db->get('playa_relationships')->result_array();

		// Index and group by the Playa's field ID
		$playa_relationships = array();
		foreach ($playa_relationships_query as $relationship)
		{
			$playa_relationships[$relationship['parent_field_id']][] = $relationship;
		}

		return $playa_relationships;
	}

	/**
	 * Adds publisher_lang_id and publisher_status columns to a table in order
	 * to preserve Publisher data
	 *
	 * @param	string	Name of table to add columns to
	 */
	public function add_publisher_columns($table_name)
	{
		ee()->load->library('smartforge');
		ee()->smartforge->add_column($table_name, array(
			'publisher_lang_id' => array(
				'type'       => 'int',
				'constraint' => 4,
				'null'       => FALSE,
				'default'    => ee()->config->item('publisher_default_language_id') ?: 1
			),
			'publisher_status' => array(
				'type'       => 'varchar',
				'constraint' => 24,
				'null'       => TRUE,
				'default'    => 'open'
			)
		));
	}
}

// EOF
