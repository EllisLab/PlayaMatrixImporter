<?php

require_once('libraries/autoload.php');
use PlayaMatrixImporter\Converters\AssetsCellConverter;

class AssetCellConverterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test AssetsCellConverter::convertData() method
	 *
	 * @dataProvider gridRelationshipsDataProvider
	 */
	public function testGridDataConversion($expected, $data, $matrix_to_grid_fields, $matrix_to_grid_cols, $description)
	{
		$new_data = AssetsCellConverter::convertData($data, $matrix_to_grid_fields, $matrix_to_grid_cols);
		$this->assertEquals($expected, $new_data, $description);
	}

	public function gridRelationshipsDataProvider()
	{
		$return = array();

		$matrix_to_grid_fields = array(
			41 => 127,
			70 => 136,
			118 => 145
		);
		$matrix_to_grid_cols = array(
			5 => 19,
			37 => 51,
			61 => 73
		);

		$data = array(
			array(
				'file_id' => '1',
				'entry_id' => '359',
				'field_id' => '118',
				'col_id' => '37',
				'row_id' => '2211',
				'var_id' => NULL,
				'element_id' => NULL,
				'content_type' => 'matrix',
				'sort_order' => '0',
				'is_draft' => '0'
			),
			array(
				'file_id' => '1',
				'entry_id' => '358',
				'field_id' => '70',
				'col_id' => '61',
				'row_id' => '2213',
				'var_id' => NULL,
				'element_id' => NULL,
				'content_type' => 'matrix',
				'sort_order' => '0',
				'is_draft' => '0'
			),
			array(
				'file_id' => '1',
				'entry_id' => '357',
				'field_id' => '41',
				'col_id' => '5',
				'row_id' => '2214',
				'var_id' => NULL,
				'element_id' => NULL,
				'content_type' => 'matrix',
				'sort_order' => '0',
				'is_draft' => '0'
			)
		);

		$expected = array(
			array(
				'file_id' => '1',
				'entry_id' => '359',
				'field_id' => '145',
				'col_id' => '51',
				'row_id' => '2211',
				'var_id' => NULL,
				'element_id' => NULL,
				'content_type' => 'grid',
				'sort_order' => '0',
				'is_draft' => '0'
			),
			array(
				'file_id' => '1',
				'entry_id' => '358',
				'field_id' => '136',
				'col_id' => '73',
				'row_id' => '2213',
				'var_id' => NULL,
				'element_id' => NULL,
				'content_type' => 'grid',
				'sort_order' => '0',
				'is_draft' => '0'
			),
			array(
				'file_id' => '1',
				'entry_id' => '357',
				'field_id' => '127',
				'col_id' => '19',
				'row_id' => '2214',
				'var_id' => NULL,
				'element_id' => NULL,
				'content_type' => 'grid',
				'sort_order' => '0',
				'is_draft' => '0'
			)
		);

		$return[] = array($expected, $data, $matrix_to_grid_fields, $matrix_to_grid_cols, 'Test empty array');

		return $return;
	}
}
