<?php

require_once('el_playamatrix_importer/libraries/autoload.php');
use PlayaMatrixImporter\Converters\TextCellConverter;

class TextCellConverterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test TextCellConverter::convertSettings() method
	 *
	 * @dataProvider settingsDataProvider
	 */
	public function testSettingsConversion($expected, $settings, $description)
	{
		$new_settings = TextCellConverter::convertSettings($settings);
		$this->assertEquals($expected, $new_settings, $description);
	}

	public function settingsDataProvider()
	{
		$return = array();

		$matrix_settings = array();

		$expected = array(
			'field_maxl'				=> 256,
			'field_content_type'		=> 'all',
			'field_text_direction'		=> 'ltr',
			'field_fmt'					=> 'none'
		);

		$return[] = array($expected, $matrix_settings, 'Test empty array');

		$matrix_settings = array(
			'maxl' => 128,
			'multiline' => 'n'
		);

		$expected['field_maxl'] = 128;

		$return[] = array($expected, $matrix_settings, 'Test conventional partial settings');

		$matrix_settings = array(
			'maxl' => 128,
			'multiline' => 'n',
			'fmt' => 'xhtml',
			'dir' => 'rtl'
		);

		$expected['field_fmt'] = 'xhtml';
		$expected['field_text_direction'] = 'rtl';

		$return[] = array($expected, $matrix_settings, 'Test conventional full settings');

		$matrix_settings = array(
			'max_value' => 100,
			'min_value' => 0
		);

		$expected = array(
			'field_maxl'				=> 256,
			'field_content_type'		=> 'integer',
			'field_text_direction'		=> 'ltr',
			'field_fmt'					=> 'none'
		);

		$return[] = array($expected, $matrix_settings, 'Test number field conversion');

		$matrix_settings = array(
			'max_value' => 332323242342423,
			'min_value' => 0
		);

		$expected['field_content_type'] = 'all';

		$return[] = array($expected, $matrix_settings, 'Test too big max value for number field');

		$matrix_settings = array(
			'max_value' => 100,
			'min_value' => -324234234232342
		);

		$return[] = array($expected, $matrix_settings, 'Test too low min value for number field');

		$matrix_settings = array(
			'max_value' => 575676575545454,
			'min_value' => -324234234232342
		);

		$return[] = array($expected, $matrix_settings, 'Test too high max value and too low min value for number field');

		$matrix_settings = array(
			'max_value' => -575676575545454,
			'min_value' => 324234234232342
		);

		$return[] = array($expected, $matrix_settings, 'Test too low max value and too high min value for number field');

		return $return;
	}

	/**
	 * Should throw an excption if trying to convert a multiline text field
	 *
	 * @expectedException Exception
	 */
	public function testBadSettingsConversion()
	{
		// Cannot convert a multiline text field into a single line
		TextCellConverter::convertSettings(array('multiline' => 'y'));
	}
}
