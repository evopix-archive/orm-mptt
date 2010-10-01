<?php

/**
 * Tests orm_mptt functionality
 * 
 * Use the following table schema to run these tests:
 * 
 * CREATE TABLE `test_orm_mptt` (
 *     `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     `parent_id` INT UNSIGNED NULL,
 *     `lft` INT UNSIGNED NOT NULL,
 *     `rgt` INT UNSIGNED NOT NULL,
 *     `lvl` INT UNSIGNED NOT NULL,
 *     `scope` INT UNSIGNED NOT NULL
 * ) ENGINE=INNODB;
 * 
 * @group orm_mptt
 * 
 * @package    ORM_MPTT
 * @author     Brandon Summers <brandon@evolutionpixels.com>
 * @copyright  2010 Brandon Summers
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */

require_once 'PHPUnit/Extensions/Database/TestCase.php';

class ORM_MPTT_Test extends PHPUnit_Extensions_Database_TestCase {

	protected function getConnection()
	{
		$db_connection = Kohana::config('unittest.db_connection');
		$db_config = Kohana::config('database.'.$db_connection);
		Database::$default = $db_connection;
		
		if ($db_config['type'] == 'mysql')
		{
			$pdo = new PDO('mysql:host='.$db_config['connection']['hostname'].';dbname='.$db_config['connection']['database'], $db_config['connection']['username'], $db_config['connection']['password']);
			return $this->createDefaultDBConnection($pdo, $db_config['connection']['database']);
		}
		else
		{
			$db_name = substr($db_config['connection']['dsn'], strpos($db_config['connection']['dsn'], ';dbname='));
			$pdo = new PDO($db_config['dsn'], $db_config['username'], $db_config['password']);
			return $this->createDefaultDBConnection($pdo, $db_name);
		}
	}

	protected function getDataSet()
	{
		return $this->createFlatXMLDataSet(Kohana::find_file('tests/orm_mptt', 'test_data/dataset', 'xml'));
	}

	/**
	 * Tests if a node has children.
	 *
	 * @test
	 * @covers ORM_MPTT::has_children
	 */
	public function test_has_children()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		
		$this->assertTrue($root_node->has_children());
		
		$no_children_node = ORM::factory('test_orm_mptt', 2);
		
		$this->assertFalse($no_children_node->has_children());
	}

	/**
	 * Tests if a node is a leaf.
	 *
	 * @test
	 * @covers ORM_MPTT::is_leaf
	 */
	public function test_is_leaf()
	{
		$non_leaf_node = ORM::factory('test_orm_mptt', 1);
		
		$this->assertFalse($non_leaf_node->is_leaf());
		
		$leaf_node = ORM::factory('test_orm_mptt', 2);
		
		$this->assertTrue($leaf_node->is_leaf());
	}

	/**
	 * Tests if a node is a descendant.
	 *
	 * @test
	 * @covers ORM_MPTT::is_descendant
	 */
	public function test_is_descendant()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		$node_1 = ORM::factory('test_orm_mptt', 2);
		$node_2 = ORM::factory('test_orm_mptt', 3);
		$node_3 = ORM::factory('test_orm_mptt', 4);
		$node_4 = ORM::factory('test_orm_mptt', 5);
		
		$this->assertTrue($node_1->is_descendant($root_node));
		$this->assertTrue($node_2->is_descendant($root_node));
		$this->assertTrue($node_3->is_descendant($root_node));
		$this->assertTrue($node_3->is_descendant($node_2));
		$this->assertTrue($node_4->is_descendant($node_2));
		
		$this->assertFalse($node_4->is_descendant($node_1));
		$this->assertFalse($node_2->is_descendant($node_3));
		$this->assertFalse($node_1->is_descendant($node_4));
	}

	/**
	 * Tests if a node is a child.
	 *
	 * @test
	 * @covers ORM_MPTT::is_child
	 */
	public function test_is_child()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		$node_1 = ORM::factory('test_orm_mptt', 2);
		$node_2 = ORM::factory('test_orm_mptt', 3);
		$node_3 = ORM::factory('test_orm_mptt', 4);
		$node_4 = ORM::factory('test_orm_mptt', 5);
		
		$this->assertTrue($node_1->is_child($root_node));
		$this->assertTrue($node_2->is_child($root_node));
		$this->assertTrue($node_3->is_child($node_2));
		$this->assertTrue($node_4->is_child($node_3));
		
		$this->assertFalse($node_3->is_child($root_node));
		$this->assertFalse($node_4->is_child($node_2));
	}

	/**
	 * Tests if a node is a parent.
	 *
	 * @test
	 * @covers ORM_MPTT::is_parent
	 */
	public function test_is_parent()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		$node_1 = ORM::factory('test_orm_mptt', 2);
		$node_2 = ORM::factory('test_orm_mptt', 3);
		$node_3 = ORM::factory('test_orm_mptt', 4);
		$node_4 = ORM::factory('test_orm_mptt', 5);
		
		$this->assertTrue($root_node->is_parent($node_1));
		$this->assertTrue($root_node->is_parent($node_2));
		$this->assertTrue($node_2->is_parent($node_3));
		$this->assertTrue($node_3->is_parent($node_4));
		
		$this->assertFalse($root_node->is_parent($node_3));
		$this->assertFalse($root_node->is_parent($node_3));
		$this->assertFalse($node_1->is_parent($node_2));
		$this->assertFalse($node_2->is_parent($node_4));
	}

	/**
	 * Tests if a node is a sibling.
	 *
	 * @test
	 * @covers ORM_MPTT::is_sibling
	 */
	public function test_is_sibling()
	{
		$node_1 = ORM::factory('test_orm_mptt', 2);
		$node_2 = ORM::factory('test_orm_mptt', 3);
		$node_3 = ORM::factory('test_orm_mptt', 4);
		$node_4 = ORM::factory('test_orm_mptt', 5);
		
		$this->assertTrue($node_1->is_sibling($node_2));
		$this->assertTrue($node_2->is_sibling($node_1));
		
		$this->assertFalse($node_3->is_sibling($node_4));
		$this->assertFalse($node_4->is_sibling($node_3));
	}

	/**
	 * Tests if a node is a root.
	 *
	 * @test
	 * @covers ORM_MPTT::is_root
	 */
	public function test_is_root()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		$node_1 = ORM::factory('test_orm_mptt', 2);
		$node_2 = ORM::factory('test_orm_mptt', 3);
		
		$this->assertTrue($root_node->is_root());
		
		$this->assertFalse($node_1->is_root());
		$this->assertFalse($node_2->is_root());
	}

	/**
	 * Tests if a node is one of the parents of a node.
	 *
	 * @test
	 * @covers ORM_MPTT::is_in_parents
	 */
	public function test_is_in_parents()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		$node_1 = ORM::factory('test_orm_mptt', 2);
		$node_2 = ORM::factory('test_orm_mptt', 3);
		$node_3 = ORM::factory('test_orm_mptt', 4);
		$node_4 = ORM::factory('test_orm_mptt', 5);
		
		$this->assertTrue($root_node->is_in_parents($node_1));
		$this->assertTrue($root_node->is_in_parents($node_2));
		$this->assertTrue($node_2->is_in_parents($node_3));
		$this->assertTrue($node_3->is_in_parents($node_4));
		
		$this->assertFalse($node_1->is_in_parents($node_2));
		$this->assertFalse($node_1->is_in_parents($node_4));
	}

	/**
	 * Tests if a the creation/moving of a root node.
	 *
	 * @test
	 * @covers ORM_MPTT::make_root
	 */
	public function test_make_root()
	{
		$new_root_node = ORM::factory('test_orm_mptt')->make_root();
		$this->assertTrue($new_root_node->is_root());
		
		$node_1 = ORM::factory('test_orm_mptt', 2)->make_root();
		$this->assertTrue($node_1->is_root());
		
		$node_2 = ORM::factory('test_orm_mptt', 5)->make_root();
		$this->assertTrue($node_2->is_root());
		
		// Make sure the space was deleted correctly
		$node_3 = ORM::factory('test_orm_mptt', 4);
		$this->assertEquals(3, $node_3->lft);
		$this->assertEquals(4, $node_3->rgt);
	}

	/**
	 * Tests inserting a node as a first child.
	 *
	 * @test
	 * @covers ORM_MPTT::insert_as_first_child
	 */
	public function test_insert_as_first_child()
	{
		$node_3 = ORM::factory('test_orm_mptt', 3);
		$node_4 = ORM::factory('test_orm_mptt', 4);
		
		$child_node = ORM::factory('test_orm_mptt')->insert_as_first_child($node_3);
		
		$node_3->reload();
		$node_4->reload();
		
		$this->assertTrue($child_node->is_child($node_3));

		// Make sure the parent_id was set correctly
		$this->assertEquals(3, $child_node->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(5, $child_node->lft);
		$this->assertEquals(11, $node_3->rgt);
		$this->assertEquals(7, $node_4->lft);
	}

	/**
	 * Tests inserting a node as a last child.
	 *
	 * @test
	 * @covers ORM_MPTT::insert_as_last_child
	 */
	public function test_insert_as_last_child()
	{
		$node_3 = ORM::factory('test_orm_mptt', 3);
		$node_4 = ORM::factory('test_orm_mptt', 4);
		
		$child_node = ORM::factory('test_orm_mptt')->insert_as_last_child($node_3);
		
		$node_3->reload();
		$node_4->reload();
		
		$this->assertTrue($child_node->is_child($node_3));

		// Make sure the parent_id was set correctly
		$this->assertEquals(3, $child_node->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(9, $child_node->lft);
		$this->assertEquals(11, $node_3->rgt);
	}

	/**
	 * Tests inserting a node as a previous sibling.
	 *
	 * @test
	 * @covers ORM_MPTT::insert_as_prev_sibling
	 */
	public function test_insert_as_prev_sibling()
	{
		$node_3 = ORM::factory('test_orm_mptt', 3);
		$node_4 = ORM::factory('test_orm_mptt', 4);
		
		$new_node = ORM::factory('test_orm_mptt')->insert_as_prev_sibling($node_4);
		
		$node_3->reload();
		$node_4->reload();
		
		$this->assertTrue($new_node->is_child($node_3));
		
		// Make sure the parent_id was set correctly
		$this->assertEquals(3, $new_node->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(5, $new_node->lft);
		$this->assertEquals(10, $node_4->rgt);
		$this->assertEquals(11, $node_3->rgt);
	}

	/**
	 * Tests inserting a node as a previous sibling.
	 *
	 * @test
	 * @covers ORM_MPTT::insert_as_next_sibling
	 */
	public function test_insert_as_next_sibling()
	{
		$node_3 = ORM::factory('test_orm_mptt', 3);
		$node_4 = ORM::factory('test_orm_mptt', 4);
		
		$new_node = ORM::factory('test_orm_mptt')->insert_as_next_sibling($node_4);
		
		$node_3->reload();
		$node_4->reload();
		
		// Make sure the parent_id was set correctly
		$this->assertEquals(3, $new_node->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(9, $new_node->lft);
		$this->assertEquals(8, $node_4->rgt);
		$this->assertEquals(11, $node_3->rgt);
	}

	/**
	 * Tests deleting a node.
	 *
	 * @test
	 * @covers ORM_MPTT::delete
	 */
	public function test_delete()
	{
		$node_4 = ORM::factory('test_orm_mptt', 4);
		$node_4->delete();
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(6, ORM::factory('test_orm_mptt', 1)->rgt);
		$this->assertEquals(5, ORM::factory('test_orm_mptt', 3)->rgt);
	}

	/**
	 * Tests moving a node to first child above it's current position.
	 *
	 * @test
	 * @covers ORM_MPTT::move_to_first_child
	 */
	public function test_move_to_first_child_above()
	{
		$node_3 = ORM::factory('test_orm_mptt', 3);
		$node_2 = ORM::factory('test_orm_mptt', 2);
		
		$node_2->move_to_first_child($node_3);
		
		$node_3->reload();
		$node_4 = ORM::factory('test_orm_mptt', 4);

		// Make sure the parent_id was set correctly
		$this->assertEquals(3, $node_2->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(3, $node_2->left());
		$this->assertEquals(4, $node_2->right());
		$this->assertEquals(9, $node_3->right());
		$this->assertEquals(5, $node_4->left());
		$this->assertEquals(8, $node_4->right());
	}

	/**
	 * Tests moving a node to first child below it's current position.
	 *
	 * @test
	 * @covers ORM_MPTT::move_to_first_child
	 */
	public function test_move_to_first_child_below()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		$node_3 = ORM::factory('test_orm_mptt', 3);
		
		$node_3->move_to_first_child($root_node);
		
		$node_2 = ORM::factory('test_orm_mptt', 2);

		// Make sure the parent_id was set correctly
		$this->assertEquals(1, $node_3->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(2, $node_3->lft);
		$this->assertEquals(7, $node_3->rgt);
		$this->assertEquals(8, $node_2->lft);
		$this->assertEquals(9, $node_2->rgt);
	}

	/**
	 * Tests moving a node to last child above it's current position.
	 *
	 * @test
	 * @covers ORM_MPTT::move_to_last_child
	 */
	public function test_move_to_last_child_above()
	{
		$node_5 = ORM::factory('test_orm_mptt', 5);
		$node_3 = ORM::factory('test_orm_mptt', 3);
		
		$node_5->move_to_last_child($node_3);
		
		$node_3->reload();
		$node_4 = ORM::factory('test_orm_mptt', 4);

		// Make sure the parent_id was set correctly
		$this->assertEquals(3, $node_5->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(7, $node_5->left());
		$this->assertEquals(8, $node_5->right());
		$this->assertEquals(9, $node_3->right());
		$this->assertEquals(5, $node_4->left());
		$this->assertEquals(6, $node_4->right());
	}

	/**
	 * Tests moving a node to last child below it's current position.
	 *
	 * @test
	 * @covers ORM_MPTT::move_to_last_child
	 */
	public function test_move_to_last_child_below()
	{
		$node_2 = ORM::factory('test_orm_mptt', 2);
		$node_3 = ORM::factory('test_orm_mptt', 3);
		
		$node_2->move_to_last_child($node_3);
		
		$node_3->reload();
		$node_4 = ORM::factory('test_orm_mptt', 4);

		// Make sure the parent_id was set correctly
		$this->assertEquals(3, $node_2->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(7, $node_2->lft);
		$this->assertEquals(8, $node_2->rgt);
		$this->assertEquals(2, $node_3->lft);
		$this->assertEquals(9, $node_3->rgt);
		$this->assertEquals(3, $node_4->lft);
		$this->assertEquals(6, $node_4->rgt);
	}

	/**
	 * Tests moving a node to last child above it's current position.
	 *
	 * @test
	 * @covers ORM_MPTT::move_to_prev_sibling
	 */
	public function test_move_to_prev_sibling_above()
	{
		$node_5 = ORM::factory('test_orm_mptt', 5);
		$node_3 = ORM::factory('test_orm_mptt', 3);
		
		$node_5->move_to_prev_sibling($node_3);
		
		$node_3->reload();
		$node_4 = ORM::factory('test_orm_mptt', 4);

		// Make sure the parent_id was set correctly
		$this->assertEquals(1, $node_5->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(4, $node_5->left());
		$this->assertEquals(5, $node_5->right());
		$this->assertEquals(6, $node_3->left());
		$this->assertEquals(9, $node_3->right());
		$this->assertEquals(7, $node_4->left());
		$this->assertEquals(8, $node_4->right());
	}

	/**
	 * Tests moving a node to last child below it's current position.
	 *
	 * @test
	 * @covers ORM_MPTT::move_to_prev_sibling
	 */
	public function test_move_to_prev_sibling_below()
	{
		$node_2 = ORM::factory('test_orm_mptt', 2);
		$node_5 = ORM::factory('test_orm_mptt', 5);
		
		$node_2->move_to_prev_sibling($node_5);
		
		$node_5->reload();
		$node_3 = ORM::factory('test_orm_mptt', 3);

		// Make sure the parent_id was set correctly
		$this->assertEquals(4, $node_2->parent_id);
		
		// Make sure the space was adjusted correctly
		$this->assertEquals(4, $node_2->lft);
		$this->assertEquals(5, $node_2->rgt);
		$this->assertEquals(2, $node_3->lft);
		$this->assertEquals(9, $node_3->rgt);
		$this->assertEquals(6, $node_5->lft);
		$this->assertEquals(7, $node_5->rgt);
	}

	/**
	 * Tests fetching child nodes
	 *
	 * @test
	 * @covers ORM_MPTT::children
	 */
	public function test_children()
	{
		$root_node = ORM::factory('test_orm_mptt', 1);
		
		$this->assertTrue($root_node->loaded());
		
		$children = $root_node->children();
		
		// Ensure we have 2 children
		$this->assertEquals(2, count($children));

		// Ensure the first child has ID = 2
		$this->assertEquals(2, $children[0]->id);

		// Ensure the second child has ID = 3
		$this->assertEquals(3, $children[1]->id);
	}

}