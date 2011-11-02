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
	 * @return  PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet()
	{
		return $this->createFlatXMLDataSet(
			Kohana::find_file('tests/datasets', 'orm/mptt/data', 'xml')
		);
	}

	/**
	 * Provides test data for test_prepend_to
	 *
	 * @return  array
	 */
	public function provider_prepend_to()
	{
		return array(
			array(
				array(
					'parent_id'  => '3',
					'parent_lft' => '4',
					'parent_rgt' => '11',
					'lft'        => '5',
					'rgt'        => '6'
				),
				NULL,
				'3',
			),
			array(
				array(
					'parent_id'  => '3',
					'parent_lft' => '2',
					'parent_rgt' => '9',
					'lft'        => '3',
					'rgt'        => '4'
				),
				'2',
				'3',
			)
		);
	}

	/**
	 * Tests that ORM_MPTT::prepend_to correctly prepends a node.
	 *
	 * @test
	 * @covers  ORM_MPTT::prepend_to
	 * @dataProvider  provider_prepend_to
	 * @param  array  $expected  Expected node data
	 * @param  string  $node_id  The id of the node to prepend
	 * @param  string  $target_id  The id of the target node
	 */
	public function test_prepend_to($expected, $node_id, $target_id)
	{
		$parent_node = new Model_TestMPTT($target_id);
		$child_node = new Model_TestMPTT($node_id);
		$child_node->prepend_to($parent_node);
		$parent_node->reload();

		$this->assertEquals($expected['lft'], $child_node->lft);
		$this->assertEquals($expected['rgt'], $child_node->rgt);
		$this->assertEquals($expected['parent_lft'], $parent_node->lft);
		$this->assertEquals($expected['parent_lft'], $parent_node->lft);
		$this->assertEquals($expected['parent_id'], $child_node->parent_id);
	}

}