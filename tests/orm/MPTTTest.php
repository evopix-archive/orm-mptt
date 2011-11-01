<?php
/**
 * Tests for ORM MPTT library.
 * 
 * @package      ORM_MPTT
 * @author       Brandon Summers
 * @copyright    (c) 2011 Brandon Summers
 * @license      https://github.com/evopix/orm-mptt/blob/master/LICENSE.md
 * 
 * @group orm
 * @group orm.mptt
 */
class ORM_MPTTTest extends Unittest_Database_TestCase {

	/**
	 * Runs before the test class as a whole is ran
	 * Creates the test table
	 */
	public static function setUpBeforeClass()
	{
		$sql = file_get_contents(Kohana::find_file('tests/datasets', 'orm/mptt/schema', 'sql'));

		Database::instance()->query(NULL, 'DROP TABLE IF EXISTS `test_orm_mptt`');
		Database::instance()->query(NULL, $sql);
	}

	/**
	 * Removes the test tables after the tests have finished
	 */
	public static function tearDownAfterClass()
	{
		Database::instance()->query(NULL, 'DROP TABLE `test_orm_mptt`');
	}

	/**
	 * Gets the dataset that should be used to populate db
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet()
	{
		return $this->createFlatXMLDataSet(
			Kohana::find_file('tests/datasets', 'orm/mptt/data', 'xml')
		);
	}

}