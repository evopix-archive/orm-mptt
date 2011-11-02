<?php defined('SYSPATH') or die('No direct script access.');
/**
 * ORM_MPTT
 * 
 * @package      ORM_MPTT
 * @author       Brandon Summers
 * @copyright    (c) 2011 Brandon Summers
 * @license      https://github.com/evopix/orm-mptt/blob/master/LICENSE.md
 */
abstract class ORM_MPTT_Core extends ORM {

	// Node positions
	const ROOT         = 1;
	const FIRST_CHILD  = 2;
	const LAST_CHILD   = 3;
	const PREV_SIBLING = 4;
	const NEXT_SIBLING = 5;

	/**
	 * @var  string  left column name
	 */
	protected $_left_column = 'lft';

	/**
	 * @var  string  right column name
	 */
	protected $_right_column = 'rgt';

	/**
	 * @var  string  level column name
	 */
	protected $_level_column = 'lvl';

	/**
	 * @var  string  scope column name
	 */
	protected $_scope_column = 'scope';

	/**
	 * @var  string  parent column name
	 */
	protected $_parent_column = 'parent_id';

	/**
	 * Checks if the current node is a descendant of the given node.
	 *
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of the target node
	 * @return  bool
	 */
	public function is_descendant(ORM_MPTT $target)
	{
		return (
				($this->get_left() > $target->get_left())
				AND ($this->get_right() < $target->get_right())
				AND ($this->get_scope() == $target->get_scope())
			);
	}

	/**
	 * Inserts or moves a node at the beginning of the target nodes tree.
	 * 
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function prepend_to(ORM_MPTT $target)
	{
		$this->set_parent($target);

		if ($this->loaded())
		{
			return $this->_move($target, ORM_MPTT::FIRST_CHILD);
		}
		else
		{
			return $this->_insert($target, ORM_MPTT::FIRST_CHILD);
		}
	}

	/**
	 * Inserts a new node to the end of the target nodes tree.
	 * 
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function append_to(ORM_MPTT $target) {}

	/**
	 * Inserts a new node before the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function insert_before(ORM_MPTT $target) {}

	/**
	 * Inserts a new node after the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function insert_after(ORM_MPTT $target) {}

	/**
	 * Moves a new node before the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function move_before(ORM_MPTT $target) {}

	/**
	 * Moves a new node after the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function move_after(ORM_MPTT $target) {}

	/**
	 * Sets the parent column value to the given targets column 
	 * value.
	 * 
	 * @access  public
	 * @param   ORM_MPTT  $target  ORM_MPTT object of the target node
	 * @param   string    $column  name of the targets nodes column to use
	 * @return  void
	 */
	public function set_parent(ORM_MPTT $target, $column = NULL)
	{
		if ($column === NULL)
		{
			$column = $target->primary_key();
		}

		if ($target->loaded())
		{
			$this->{$this->_parent_column} = $target->{$column};
		}
		else
		{
			$this->{$this->_parent_column} = NULL;
		}
	}

	/**
	 * Sets the left column value to the given value.
	 * 
	 * @access  public
	 * @param   int  $value  Integer to set the left column to
	 * @return  void
	 */
	public function set_left($value)
	{
		$this->{$this->_left_column} = (int) $value;
	}

	/**
	 * Sets the right column value to the given value.
	 * 
	 * @access  public
	 * @param   int  $value  Integer to set the right column to
	 * @return  void
	 */
	public function set_right($value)
	{
		$this->{$this->_right_column} = (int) $value;
	}

	/**
	 * Sets the level column value to the given value.
	 * 
	 * @access  public
	 * @param   int  $value  Integer to set the level column to
	 * @return  void
	 */
	public function set_level($value)
	{
		$this->{$this->_level_column} = (int) $value;
	}

	/**
	 * Sets the scope column value to the given value.
	 * 
	 * @access  public
	 * @param   int  $value  Integer to set the scope column to
	 * @return  void
	 */
	public function set_scope($value)
	{
		$this->{$this->_scope_column} = (int) $value;
	}

	/**
	 * Gets the parent column value for the current node.
	 * 
	 * @access  public
	 * @return  int
	 */
	public function get_parent()
	{
		return (int) $this->{$this->_parent_column};
	}

	/**
	 * Gets the left column value for the current node.
	 * 
	 * @access  public
	 * @return  int
	 */
	public function get_left()
	{
		return (int) $this->{$this->_left_column};
	}

	/**
	 * Gets the right column value for the current node.
	 * 
	 * @access  public
	 * @return  int
	 */
	public function get_right()
	{
		return (int) $this->{$this->_right_column};
	}

	/**
	 * Gets the level column value for the current node.
	 * 
	 * @access  public
	 * @return  int
	 */
	public function get_level()
	{
		return (int) $this->{$this->_level_column};
	}

	/**
	 * Gets the scope column value for the current node.
	 * 
	 * @access  public
	 * @return  int
	 */
	public function get_scope()
	{
		return (int) $this->{$this->_scope_column};
	}

	/**
	 * Gets the size of the current node.
	 * 
	 * @access  public
	 * @return  int
	 */
	public function get_size()
	{
		return (int) ($this->get_right() - $this->get_left()) + 1;
	}

	/**
	 * Inserts a new node in the tree.
	 *
	 * @access  protected
	 * @param   ORM_MPTT  $target    ORM_MPTT object of target node
	 * @param   int       $position  Position to insert the node at
	 * @return  ORM_MPTT
	 */
	protected function _insert($target, $position)
	{
		// make sure target is up-to-date
		$target->reload();

		switch ($position)
		{
			case 2:
				$copy_left_from = $this->_left_column;
				$left_offset = 1;
				$level_offset = 1;
			break;
			case 3:
				$copy_left_from = $this->_right_column;
				$left_offset = 0;
				$level_offset = 1;
			break;
			case 4:
				$copy_left_from = $this->_left_column;
				$left_offset = 0;
				$level_offset = 0;
			break;
			case 5:
				$copy_left_from = $this->_right_column;
				$left_offset = 1;
				$level_offset = 0;
			break;
			default:
				throw new ORM_MPTT_Exception('Invalid node position '.$position.'.');
			break;
		}

		$this->set_left($target->{$copy_left_from} + $left_offset);
		$this->set_right($this->get_left() + 1);
		$this->set_level($target->get_level() + $level_offset);
		$this->set_scope($target->get_scope());

		$this->_db->begin();

		try
		{
			$this->_create_space($this->get_left());
			parent::create();
		}
		catch (Exception $e)
		{
			$this->_db->rollback();
			throw $e;
		}

		$this->_db->commit();
		return $this;
	}

	/**
	 * Moves a new node to a different position in the tree.
	 *
	 * @access  protected
	 * @param   ORM_MPTT  $target    ORM_MPTT object of target node
	 * @param   int       $position  Position to insert the node at
	 * @return  ORM_MPTT
	 */
	protected function _move($target, $position)
	{
		switch ($position)
		{
			case ORM_MPTT::FIRST_CHILD:
				$left_column = TRUE;
				$left_offset = 1;
				$level_offset = 1;
				$allow_root_target = TRUE;
			break;
			case ORM_MPTT::LAST_CHILD:
				$left_column = FALSE;
				$left_offset = 0;
				$level_offset = 1;
				$allow_root_target = TRUE;
			break;
			case ORM_MPTT::PREV_SIBLING:
				$left_column = TRUE;
				$left_offset = 0;
				$level_offset = 0;
				$allow_root_target = FALSE;
			break;
			case ORM_MPTT::NEXT_SIBLING:
				$left_column = FALSE;
				$left_offset = 1;
				$level_offset = 0;
				$allow_root_target = FALSE;
			break;
			default:
				throw new ORM_MPTT_Exception('Invalid node position '.$position.'.');
			break;
		}

		// store the changed parent id before reload
		$parent_id = $this->get_parent();

		$this->_db->begin();
		$this->reload();

		try
		{
			$target->reload();

			if ($target->is_descendant($this) OR ($this->pk() === $target->pk()))
			{
				$this->_db->rollback();
				throw new ORM_MPTT_Exception('A node cannot be moved into a descendant of itself.');
			}

			if (( ! $allow_root_target) AND $target->is_root())
			{
				$this->_db->rollback();
				throw new ORM_MPTT_Exception('A node cannot be a sibling of a root node.');
			}

			if ($level_offset > 0)
			{
				// We're moving to a child node so add 1 to left offset
				$left_offset = ($left_column === TRUE) ? ($target->get_left() + 1) : ($target->get_right() + $left_offset);
			}
			else
			{
				$left_offset = ($left_column === TRUE) ? $target->get_left() : ($target->get_right() + $left_offset);
			}

			$level_offset = ($target->get_level() - $this->get_level()) + $level_offset;
			$size = $this->get_size();

			$this->_create_space($left_offset, $size);

			$this->reload();

			$offset = ($left_offset - $this->get_left());

			DB::update($this->table_name())
				->set(array($this->_left_column => DB::expr($this->_left_column.' + '.$offset)))
				->set(array($this->_right_column => DB::expr($this->_right_column.' + '.$offset)))
				->set(array($this->_level_column => DB::expr($this->_level_column.' + '.$level_offset)))
				->set(array($this->_scope_column => $this->get_scope()))
				->where_open()
				->where($this->_left_column,'>=', $this->get_left())
				->where($this->_right_column,'<=', $this->get_right())
				->where($this->_scope_column, '=', $this->get_scope())
				->where_close()
				->execute($this->_db);

			$this->_delete_space($this->get_left(), $size);

			if ($parent_id != $this->get_parent())
			{
				$this->{$this->_parent_column} = $parent_id;
				$this->update();
			}
		}
		catch (Exception $e)
		{
			$this->_db->rollback();
			throw $e;
		}

		$this->_db->commit();
		$this->reload();

		return $this;
	}

	/**
	 * Adds space to the tree for inserting/moving nodes.
	 * 
	 * @access  protected
	 * @param   int   $start  start position
	 * @param   int   $size   size of the gap to add [optional]
	 * @return  void
	 */
	protected function _create_space($start, $size = 2)
	{
		DB::update($this->table_name())
			->set(array($this->_left_column => DB::expr($this->_left_column.' + '.$size)))
			->where($this->_left_column,'>=', $start)
			->where($this->_scope_column, '=', $this->get_scope())
			->execute($this->_db);

		DB::update($this->table_name())
			->set(array($this->_right_column => DB::expr($this->_right_column.' + '.$size)))
			->where($this->_right_column,'>=', $start)
			->where($this->_scope_column, '=', $this->get_scope())
			->execute($this->_db);
	}

	/**
	 * Removes space from the tree after deleting/moving nodes.
	 * 
	 * @access  protected
	 * @param   int   $start  start position
	 * @param   int   $size   size of the gap to remove [optional]
	 * @return  void
	 */
	protected function _delete_space($start, $size = 2)
	{
		DB::update($this->table_name())
			->set(array($this->_left_column => DB::expr($this->_left_column.' - '.$size)))
			->where($this->_left_column, '>=', $start)
			->where($this->_scope_column, '=', $this->get_scope())
			->execute($this->_db);

		DB::update($this->table_name())
			->set(array($this->_right_column => DB::expr($this->_right_column.' - '.$size)))
			->where($this->_right_column,'>=', $start)
			->where($this->_scope_column, '=', $this->get_scope())
			->execute($this->_db);
	}

}