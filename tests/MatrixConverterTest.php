<?php

require_once('libraries/autoload.php');
use PlayaMatrixImporter\Converters\MatrixConverter;

class MatrixConverterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test MatrixConverter::mapColumnType() method
	 *
	 * @dataProvider columnTypeDataProvider
	 */
	public function testColumnTypeMapping($expected, $column_type, $settings, $grid_fieldtypes, $description)
	{
		$mapped_column_type = MatrixConverter::mapColumnType($column_type, $settings, $grid_fieldtypes);
		$this->assertEquals($expected, $mapped_column_type, $description);
	}

	public function columnTypeDataProvider()
	{
		$return = array();

		$grid_fieldtypes = array(
			'checkboxes',
			'date',
			'file',
			'multi_select',
			'radio',
			'select',
			'text',
			'textarea',
			'rte'
		);

		$settings = array();

		$return[] = array('date', 'date', $settings, $grid_fieldtypes, 'Test date mapping');
		$return[] = array('checkboxes', 'fieldpack_checkboxes', $settings, $grid_fieldtypes, 'Test fieldpack checkboxes mapping');
		$return[] = array('select', 'fieldpack_dropdown', $settings, $grid_fieldtypes, 'Test fieldpack dropdown mapping');
		$return[] = array('textarea', 'fieldpack_list', $settings, $grid_fieldtypes, 'Test fieldpack list mapping');
		$return[] = array('multi_select', 'fieldpack_multiselect', $settings, $grid_fieldtypes, 'Test fieldpack multiselect mapping');
		$return[] = array('radio', 'fieldpack_pill', $settings, $grid_fieldtypes, 'Test fieldpack pill mapping');
		$return[] = array('radio', 'fieldpack_radio_buttons', $settings, $grid_fieldtypes, 'Test fieldpack radio buttons mapping');
		$return[] = array('radio', 'fieldpack_switch', $settings, $grid_fieldtypes, 'Test fieldpack switch mapping');
		$return[] = array('file', 'file', $settings, $grid_fieldtypes, 'Test file mapping');
		$return[] = array('text', 'number', $settings, $grid_fieldtypes, 'Test number mapping');
		$return[] = array('relationship', 'playa', $settings, $grid_fieldtypes, 'Test playa mapping');
		$return[] = array('rte', 'rte', $settings, $grid_fieldtypes, 'Test rte mapping');
		$return[] = array('text', 'text', $settings, $grid_fieldtypes, 'Test text mapping');

		$settings = array('multiline' => 'y');

		$return[] = array('textarea', 'text', $settings, $grid_fieldtypes, 'Test mutliline text field to textarea mapping');

		$settings = array();

		$return[] = array('text', 'wygwam', $settings, $grid_fieldtypes, 'Test incompatible fieldtypes fall back to text');

		$grid_fieldtypes[] = 'wygwam';

		$return[] = array('wygwam', 'wygwam', $settings, $grid_fieldtypes, 'Test compatible fieldtypes are the same');

		return $return;
	}

	/**
	 * Test MatrixConverter::convertMatrixColumns() method
	 *
	 * @dataProvider columnDataProvider
	 */
	public function testColumnConversion($expected, $columns, $field_id, $description)
	{
		$grid_fieldtypes = array(
			'checkboxes',
			'date',
			'file',
			'multi_select',
			'radio',
			'select',
			'text',
			'textarea',
			'rte'
		);

		$new_columns = MatrixConverter::convertMatrixColumns($columns, $grid_fieldtypes, $field_id);

		foreach ($new_columns as $col_id => $value)
		{
			unset($new_columns[$col_id]['col_settings']);
		}

		$this->assertEquals($expected, $new_columns, $description);
	}

	public function columnDataProvider()
	{
		$return = array();

		$columns = array(
			array(
				'col_id' => 5,
				'site_id' => 1,
				'field_id' => 40,
				'var_id' => NULL,
				'col_name' => 'hello',
				'col_label' => 'My field',
				'col_instructions' => NULL,
				'col_type' => 'text',
				'col_required' => 'y',
				'col_search' => 'n',
				'col_order' => 0,
				'col_width' => NULL,
				'col_settings' => 'YTowOnt9'
			),
			array(
				'col_id' => 6,
				'site_id' => 1,
				'field_id' => 40,
				'var_id' => NULL,
				'col_name' => 'number',
				'col_label' => 'My number field',
				'col_instructions' => 'put a number',
				'col_type' => 'number',
				'col_required' => 'y',
				'col_search' => 'n',
				'col_order' => 1,
				'col_width' => '50%',
				'col_settings' => 'YTowOnt9'
			),
			array(
				'col_id' => 7,
				'site_id' => 1,
				'field_id' => 40,
				'var_id' => NULL,
				'col_name' => '',
				'col_label' => '',
				'col_instructions' => NULL,
				'col_type' => 'fieldpack_radio_buttons',
				'col_required' => 'n',
				'col_search' => 'y',
				'col_order' => 2,
				'col_width' => '',
				'col_settings' => 'YTowOnt9'
			)
		);

		$expected = array(
			5 => array(
				'field_id' => 28,
				'content_type' => 'channel',
				'col_order' => 0,
				'col_type' => 'text',
				'col_label' => 'My field',
				'col_name' => 'hello',
				'col_instructions' => NULL,
				'col_required' => 'y',
				'col_search' => 'n',
				'col_width' => 0
			),
			6 => array(
				'field_id' => 28,
				'content_type' => 'channel',
				'col_order' => 1,
				'col_type' => 'text',
				'col_label' => 'My number field',
				'col_name' => 'number',
				'col_instructions' => 'put a number',
				'col_required' => 'y',
				'col_search' => 'n',
				'col_width' => 50
			),
			7 => array(
				'field_id' => 28,
				'content_type' => 'channel',
				'col_order' => 2,
				'col_type' => 'radio',
				'col_label' => 'Column 7',
				'col_name' => 'col_id_7',
				'col_instructions' => NULL,
				'col_required' => 'n',
				'col_search' => 'y',
				'col_width' => 0
			)
		);

		$return[] = array($expected, $columns, 28, 'Test various column inputs');

		return $return;
	}
}
