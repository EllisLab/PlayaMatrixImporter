<?php

require_once('el_playamatrix_importer/libraries/autoload.php');
use PlayaMatrixImporter\Converters\OptionsCellConverter;

class OptionsCellConverterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test OptionsCellConverter::convertSettings() method
	 *
	 * @dataProvider settingsDataProvider
	 */
	public function testSettingsConversion($expected, $settings, $description)
	{
		$new_settings = OptionsCellConverter::convertSettings($settings);
		$this->assertEquals($expected, $new_settings, $description);
	}

	public function settingsDataProvider()
	{
		$return = array();

		$matrix_settings = array(
			'options' => array(
				'this' => 'This',
				'is' => 'Is',
				'a' => 'A',
				'test' => 'Test'
			)
		);

		$expected = array(
			'field_pre_populate' => 'n',
			'field_fmt' => 'none',
			'field_list_items' => "This\nIs\nA\nTest"
		);

		$return[] = array($expected, $matrix_settings, 'Test standard key value options');

		$matrix_settings = array(
			'off_label' => 'NO',
			'off_val' => '',
			'on_label' => 'YES',
			'on_val' => 'y',
			'default' => 'off'
		);

		$expected = array(
			'field_pre_populate' => 'n',
			'field_fmt' => 'none',
			'field_list_items' => "NO\nYES"
		);

		$return[] = array($expected, $matrix_settings, 'Test switch options');

		return $return;
	}

	/**
	 * Test OptionsCellConverter::convertData() method
	 *
	 * @dataProvider cellDataProvider
	 */
	public function testDataConversion($expected, $data, $settings, $description)
	{
		$new_data = OptionsCellConverter::convertData($data, $settings);
		$this->assertEquals($expected, $new_data, $description);
	}

	public function cellDataProvider()
	{
		$return = array();

		$matrix_settings = array(
			'options' => array(
				'this' => 'This',
				'is' => 'Is',
				'a' => 'A',
				'test' => 'Test'
			)
		);

		$data = "this\nis\na\ntest";

		$expected = 'This|Is|A|Test';

		$return[] = array($expected, $data, $matrix_settings, 'Test standard key value data');

		$matrix_settings = array(
			'off_label' => 'NO',
			'off_val' => '',
			'on_label' => 'YES',
			'on_val' => 'y',
			'default' => 'off'
		);

		$data = 'y';

		$expected = 'YES';

		$return[] = array($expected, $data, $matrix_settings, 'Test switch data');

		return $return;
	}
}
