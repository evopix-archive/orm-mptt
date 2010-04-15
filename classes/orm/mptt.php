<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Modified Preorder Tree Traversal Class.
 * 
 * A port of Banks' Sprig_MPTT plus some code from BIakaVeron's ORM_MPTT module.
 *
 * @author Mathew Davies
 * @author Kiall Mac Innes
 * @author Paul Banks
 * @author Brotkin Ivan
 * @author Brandon Summers
 */

class ORM_MPTT extends ORM {

	/**
	 * @access  public
	 * @var     string  left column name
	 */
	public $left_column = NULL;

	/**
	 * @access  public
	 * @var     string  right column name
	 */
	public $right_column = NULL;

	/**
	 * @access  public
	 * @var     string  level column name
	 */
	public $level_column = NULL;

	/**
	 * @access  public
	 * @var     string  scope column name
	 */
	public $scope_column = NULL;

	/**
	 * @access  public
	 * @var     string  parent column name
	 */
	public $parent_column = NULL;

	/**
	 * Load the default column names.
	 *
	 * @access  public
	 * @param   mixed   parameter for find or object to load
	 * @return  void
	 */
	public function __construct($id = NULL)
	{
		$config = Kohana::config('mptt');
		
		foreach ($config as $param => $value)
		{
			$this->{$param} = $value;
		}
		
		if ( ! isset($this->_sorting))
		{
			$this->_sorting = array($this->left_column => 'ASC');
		}
		
		parent::__construct($id);
	}

	/**
	 * Checks if the current node has any children.
	 * 
	 * @access  public
	 * @return  bool
	 */
	public function has_children()
	{
		return ($this->size() > 2);
	}

	/**
	 * Is the current node a leaf node?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function is_leaf()
	{
		return ( ! $this->has_children());
	}

	/**
	 * Is the current node a descendant of the supplied node.
	 *
	 * @access  public
	 * @param   ORM_MPTT|integer  ORM_MPTT object or primary key value of target node
	 * @return  bool
	 */
	public function is_descendant($target)
	{
		if ( ! ($target instanceof $this))
		{
			$target = self::factory($this->object_name(), $target);
		}
		
		return (
				$this->{$this->left_column} > $target->{$target->left_column}
				AND $this->{$this->right_column} < $target->{$target->right_column}
				AND $this->{$this->scope_column} = $target->{$target->scope_column}
			);
	}

	/**
	 * Checks if the current node is a direct child of the supplied node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT|integer  ORM_MPTT object or primary key value of target node
	 * @return  bool
	 */
	public function is_child($target)
	{
		if ( ! ($target instanceof $this))
		{
			$target = self::factory($this->object_name(), $target);
		}

		return ($this->parent->pk() === $target->pk());
	}

	/**
	 * Checks if the current node is a direct parent of a specific node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT|integer  ORM_MPTT object or primary key value of child node
	 * @return  bool
	 */
	public function is_parent($target)
	{
		if ( ! ($target instanceof $this))
		{
			$target = self::factory($this->object_name(), $target);
		}

		return ($this->pk() === $target->parent->pk());
	}

	/**
	 * Checks if the current node is a sibling of a supplied node.
	 * (Both have the same direct parent)
	 * 
	 * @access  public
	 * @param   ORM_MPTT|integer  ORM_MPTT object or primary key value of target node
	 * @return  bool
	 */
	public function is_sibling($target)
	{
		if ( ! ($target instanceof $this))
		{
			$target = self::factory($this->object_name(), $target);
		}
		
		if ($this->pk() === $target->pk())
			return FALSE;

		return ($this->parent->pk() === $target->parent->pk());
	}

	/**
	 * Checks if the current node is a root node.
	 * 
	 * @access  public
	 * @return  bool
	 */
	public function is_root()
	{
		return ($this->left() === 1);
	}

	/**
	 * Checks if the current node is one of the parents of a specific node.
	 * 
	 * @access  public
	 * @param   integer|object  id or object of parent node
	 * @return  bool
	 */
	public function is_in_parents($target)
	{
		if ( ! ($target instanceof $this))
		{
			$target = self::factory($this->object_name(), $target);
		}

		return $target->is_descendant($this);
	}

	/**
	 * Overloaded save method.
	 * 
	 * @access  public
	 * @return  mixed
	 */
	public function save()
	{
		if ( ! $this->loaded())
		{
			return $this->make_root();
		}
		elseif ($this->loaded() === TRUE)
		{
			return parent::save();
		}
		
		return FALSE;
	}

	/**
	 * Creates a new node as root, or moves a node to root
	 *
	 * @return  ORM_MPTT
	 */
	public function make_root()
	{
		// If node already exists, and already root, exit
		if ($this->loaded() && $this->is_root()) return $this;

		// delete node space first
		if ($this->loaded())
		{
			$this->delete_space($this->left(), $this->size());
		}

		// Increment next scope
		$scope = self::get_next_scope();

		$this->{$this->scope_column} = $scope;
		$this->{$this->level_column} = 1;
		$this->{$this->left_column} = 1;
		$this->{$this->right_column} = 2;
		$this->{$this->parent_column} = NULL;

		try
		{
			parent::save();
		}
		catch (Validate_Exception $e)
		{
			// Some fields didn't validate, throw an exception
			throw $e;
		}

		return $this;
	}

	/**
	 * Saves the current object as the root of a new scope.
	 *
	 * @access  public
	 * @param   integer   the new scope
	 * @return  ORM_MPTT
	 * @throws  Validation_Exception
	 */
	public function insert_as_new_root($scope = NULL)
	{
		// Cannot insert the same node twice
		if ($this->loaded())
			return FALSE;

		if (is_null($scope))
		{
			$scope = self::get_next_scope();
		}
		elseif ( ! self::scope_available($scope))
		{
			return FALSE;
		}

		$this->{$this->scope_column} = $scope;
		$this->{$this->level_column} = 1;
		$this->{$this->left_column} = 1;
		$this->{$this->right_column} = 2;
		$this->{$this->parent_column} = NULL;
		
		try
		{
			parent::save();
		}
		catch (Validate_Exception $e)
		{
			// Some fields didn't validate, throw an exception
			throw $e;
		}
		
		return $this;
	}
	
	/**
	 * Inserts a new node as the first child of the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT|integer  primary key value or ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function insert_as_first_child($target)
	{
		return $this->insert($target, $this->left_column, 1, 1);
	}
	
	/**
	 * Inserts a new node as the last child of the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT|integer  primary key value or ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function insert_as_last_child($target)
	{
		return $this->insert($target, $this->right_column, 0, 1);
	}
	
	/**
	 * Inserts a new node as a previous sibling of the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT|integer  primary key value or ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function insert_as_prev_sibling($target)
	{
		return $this->insert($target, $this->left_column, 0, 0);
	}
	
	/**
	 * Inserts a new node as the next sibling of the target node.
	 * 
	 * @access  public
	 * @param   ORM_MPTT|integer  primary key value or ORM_MPTT object of target node
	 * @return  ORM_MPTT
	 */
	public function insert_as_next_sibling($target)
	{
		return $this->insert($target, $this->right_column, 1, 0);
	}
	
	/**
	 * Insert the object
	 *
	 * @access  protected
	 * @param   ORM_MPTT|integer  primary key value or ORM_MPTT object of target node.
	 * @param   string  target object property to take new left value from
	 * @param   integer  offset for left value
	 * @param   integer  offset for level value
	 * @return  ORM_MPTT
	 * @throws  Validation_Exception
	 */
	protected function insert($target, $copy_left_from, $left_offset, $level_offset)
	{
		// Insert should only work on new nodes.. if its already it the tree it needs to be moved!
		if ($this->loaded())
			return FALSE;
		 
		 
		 
		if ( ! $target instanceof $this)
		{
			$target = self::factory($this->object_name(), array($this->primary_key() => $target));
		 
			if ( ! $target->loaded())
			{
				return FALSE;
			}
		}
		else
		{
			$target->reload();
		}
		 
		$this->lock();
		 
		$this->{$this->left_column} = $target->{$copy_left_from} + $left_offset;
		$this->{$this->right_column} = $this->{$this->left_column} + 1;
		$this->{$this->level_column} = $target->{$this->level_column} + $level_offset;
		$this->{$this->scope_column} = $target->{$this->scope_column};
		 
		$this->create_space($this->{$this->left_column});
		 
		try
		{
			parent::save();
		}
		catch (Validate_Exception $e)
		{
			// We had a problem saving, make sure we clean up the tree
			$this->delete_space($this->left());
			$this->unlock();
			throw $e;
		}
		 
		$this->unlock();
		 
		return $this;
	}

	/**
	 * Deletes the current node and all descendants.
	 * 
	 * @access  public
	 * @return  void
	 */
	public function delete($query = NULL)
	{
		if ($query !== NULL)
		{
			throw new Kohana_Exception('ORM_MPTT does not support passing a query object to delete()');
		}
		
		$this->lock();

		try
		{
			DB::delete($this->_table_name)
				->where($this->left_column,' >=',$this->left())
				->where($this->right_column,' <= ',$this->right())
				->where($this->scope_column,' <= ',$this->scope())
				->execute($this->_db);

			$this->delete_space($this->left(), $this->size());
		}
		catch (Kohana_Exception $e)
		{
			$this->unlock();
			throw $e;
		}

		$this->unlock();
	}
	
	public function move_to_first_child($target)
	{
		return $this->move($target, TRUE, 1, 1, TRUE);
	}
	
	public function move_to_last_child($target)
	{
		return $this->move($target, FALSE, 0, 1, TRUE);
	}
	
	public function move_to_prev_sibling($target)
	{
		return $this->move($target, TRUE, 0, 0, FALSE);
	}
	
	public function move_to_next_sibling($target)
	{
		return $this->move($target, FALSE, 1, 0, FALSE);
	}
	
	protected function move($target, $left_column, $left_offset, $level_offset, $allow_root_target)
	{
		if ( ! $this->loaded())
			return FALSE;
		
		// Make sure we have the most upto date version of this AFTER we lock
		$this->lock();
		$this->reload();
		 
		// Catch any database or other excpetions and unlock
		try
		{
			if ( ! $target instanceof $this)
			{
				$target = self::factory($this->object_name(), array($this->primary_key() => $target));
				 
				if ( ! $target->loaded())
				{
					$this->unlock();
					return FALSE;
				}
			}
			else
			{
				$target->reload();
			}

			// Stop $this being moved into a descendant or itself or disallow if target is root
			if ($target->is_descendant($this)
				OR $this->{$this->primary_key()} === $target->{$this->primary_key()}
				OR ($allow_root_target === FALSE AND $target->is_root()))
			{
				$this->unlock();
				return FALSE;
			}

			$left_offset = ($left_column === TRUE ? $target->left() : $target->right()) + $left_offset;
			$level_offset = $target->level() - $this->level() + $level_offset;
			$size = $this->size();
			$this->create_space($left_offset, $size);
			
			$this->reload();
			
			$offset = ($left_offset - $this->left());

			$this->_db->query(NULL, 'UPDATE '.$this->_table_name.'
				SET `'.$this->left_column.'` = `'.$this->left_column.'` + '.$offset.', `'.$this->right_column.'` = `'.$this->right_column.'` + '.$offset.'
				, `'.$this->level_column.'` = `'.$this->level_column.'` + '.$level_offset.'
				, `'.$this->scope_column.'` = '.$target->{$this->scope_column}.'
				WHERE `'.$this->left_column.'` >= '.$this->{$this->left_column}.'
				AND `'.$this->right_column.'` <= '.$this->{$this->right_column}.'
				AND `'.$this->scope_column.'` = '.$this->{$this->scope_column}, TRUE);
			 
			$this->delete_space($this->left(), $size);
		}
		catch (Kohana_Exception $e)
		{
			// Unlock table and re-throw exception
			$this->unlock();
			throw $e;
		}
		 
		$this->unlock();
		 
		return $this;
	}

	/**
	 * Returns the next available value for scope.
	 *
	 * @access  protected
	 * @return  integer
	 **/
	protected function get_next_scope()
	{
		$scope = DB::select(DB::expr('IFNULL(MAX(`'.$this->scope_column.'`), 0) as scope'))
				->from($this->_table_name)
				->execute($this->_db)
				->current();

		if ($scope AND intval($scope['scope']) > 0)
			return intval($scope['scope']) + 1;

		return 1;
	}

	/**
	 * Returns current or all root node/s
	 * 
	 * @access  public
	 * @param   integer         scope
	 * @return  ORM_MPTT|FALSE
	 */
	public function root($scope = NULL)
	{
		if (is_null($scope) AND $this->loaded())
		{
			$scope = $this->scope();
		}
		elseif (is_null($scope) AND ! $this->loaded())
		{
			return FALSE;
		}
		
		return self::factory($this->object_name(), array($this->left_column => 1, $this->scope_column => $scope));
	}

	/**
	 * Returns all root node's
	 * 
	 * @access  public
	 * @return  ORM_MPTT
	 */
	public function roots()
	{
		return self::factory($this->object_name())
				->where($this->left_column, '=', 1)
				->find_all();
	}

	/**
	 * Returns the parent node of the current node
	 * 
	 * @access  public
	 * @return  ORM_MPTT
	 */
	public function parent()
	{
		if ($this->is_root())
			return NULL;

		return self::factory($this->object_name(), $this->{$this->parent_column});
	}

	/**
	 * Returns all of the current nodes parents.
	 * 
	 * @access  public
	 * @param   bool      include root node
	 * @param   bool      include current node
	 * @param   string    direction to order the left column by
	 * @param   bool      retrieve the direct parent only
	 * @return  ORM_MPTT
	 */
	public function parents($root = TRUE, $with_self = FALSE, $direction = 'ASC', $direct_parent_only = FALSE)
	{
		$suffix = $with_self ? '=' : '';

		$query = self::factory($this->object_name())
			->where($this->left_column, '<'.$suffix, $this->left())
			->where($this->right_column, '>'.$suffix, $this->right())
			->where($this->scope_column, '=', $this->scope())
			->order_by($this->left_column, $direction);
		
		if ( ! $root)
		{
			$query->where($this->left_column, '!=', 1);
		}
		
		if ($direct_parent_only)
		{
			$query
				->where($this->level_column, '=', $this->level() - 1)
				->limit(1);
		}
		
		return $query->find_all();
	}

	/**
	 * Returns direct children of the current node.
	 * 
	 * @access  public
	 * @param   bool     include the current node
	 * @param   string   direction to order the left column by
	 * @param   integer  number of children to get
	 * @return  ORM_MPTT
	 */
	public function children($self = FALSE, $direction = 'ASC', $limit = FALSE)
	{
		return $this->descendants($self, $direction, TRUE, FALSE, $limit);
	}

	/**
	 * Returns a full hierarchical tree, with or without scope checking.
	 * 
	 * @access  public
	 * @param   bool    only retrieve nodes with specified scope
	 * @return  object
	 */
	public function fulltree($scope = NULL)
	{
		$result = self::factory($this->object_name());

		if ( ! is_null($scope))
		{
			$result->where($this->scope_column, '=', $scope);
		}
		else
		{
			$result->order_by($this->scope_column, 'ASC')
					->order_by($this->left_column, 'ASC');
		}

		return $result->find_all();
	}
	
	/**
	 * Returns the siblings of the current node
	 *
	 * @access  public
	 * @param   bool  include the current node
	 * @param   string  direction to order the left column by
	 * @return  ORM_MPTT
	 */
	public function siblings($self = FALSE, $direction = 'ASC')
	{
		$query = self::factory($this->object_name())
			->where($this->left_column, '>', $this->parent->left())
			->where($this->right_column, '<', $this->parent->right())
			->where($this->scope_column, '=', $this->scope())
			->where($this->level_column, '=', $this->level())
			->order_by($this->left_column, $direction);
		 
		if ( ! $self)
		{
			$query->where($this->primary_key, '<>', $this->pk());
		}
		 
		return $query->find_all();
	}

	/**
	 * Returns the leaves of the current node.
	 * 
	 * @access  public
	 * @param   bool  include the current node
	 * @param   string  direction to order the left column by
	 * @return  ORM_MPTT
	 */
	public function leaves($self = FALSE, $direction = 'ASC')
	{
		return $this->descendants($self, $direction, TRUE, TRUE);
	}
	
	/**
	 * Returns the descendants of the current node.
	 *
	 * @access  public
	 * @param   bool  include the current node
	 * @param   string  direction to order the left column by.
	 * @param   bool  include direct children only
	 * @param   bool  include leaves only
	 * @param   integer  number of results to get
	 * @return  ORM_MPTT
	 */
	public function descendants($self = FALSE, $direction = 'ASC', $direct_children_only = FALSE, $leaves_only = FALSE, $limit = FALSE)
	{
		$left_operator = $self ? '>=' : '>';
		$right_operator = $self ? '<=' : '<';
		
		$query = self::factory($this->object_name())
			->where($this->left_column, $left_operator, $this->left())
			->where($this->right_column, $right_operator, $this->right())
			->where($this->scope_column, '=', $this->scope())
			->order_by($this->left_column, $direction);
		
		if ($direct_children_only)
		{
			if ($self)
			{
				$query
					->and_where_open()
					->where($this->level_column, '=', $this->level())
					->or_where($this->level_column, '=', $this->level() + 1)
					->and_where_close();
			}
			else
			{
				$query->where($this->level_column, '=', $this->level() + 1);
			}
		}
		
		if ($leaves_only)
		{
			$query->where($this->right_column, '=', DB::expr($this->left_column.' + 1'));
		}
		
		if ($limit !== FALSE)
		{
			$query->limit($limit);
		}
		
		return $query->find_all();
	}

	/**
	 * Adds space to the tree for adding or inserting nodes.
	 * 
	 * @access  protected
	 * @param   integer    start position
	 * @param   integer    size of the gap to add [optional]
	 * @return  void
	 */
	protected function create_space($start, $size = 2)
	{
		DB::update($this->_table_name)
			->set(array($this->left_column => DB::expr($this->left_column.' + '.$size)))
			->where($this->left_column,'>=', $start)
			->where($this->scope_column, '=', $this->scope())
			->execute($this->_db);

		DB::update($this->_table_name)
			->set(array($this->right_column => DB::expr($this->right_column.' + '.$size)))
			->where($this->right_column,'>=', $start)
			->where($this->scope_column, '=', $this->scope())
			->execute($this->_db);
	}

	/**
	 * Removes space from the tree after deleting or moving nodes.
	 * 
	 * @access  protected
	 * @param   integer    start position
	 * @param   integer    size of the gap to remove [optional]
	 * @return  void
	 */
	protected function delete_space($start, $size = 2)
	{
		DB::update($this->_table_name)
			->set(array($this->left_column => DB::expr($this->left_column.' - '.$size)))
			->where($this->left_column, '>=', $start)
			->where($this->scope_column, '=', $this->scope())
			->execute($this->_db);

		DB::update($this->_table_name)
			->set(array($this->right_column => DB::expr($this->right_column.' - '.$size)))
			->where($this->right_column,'>=', $start)
			->where($this->scope_column, '=', $this->scope())
			->execute($this->_db);
	}

	/**
	 * Locks the current table.
	 * 
	 * @access  protected
	 * @return  void
	 */
	protected function lock()
	{
		$this->_db->query(NULL, 'LOCK TABLE '.$this->_table_name.' WRITE', TRUE);
	}

	/**
	 * Unlocks the current table.
	 * 
	 * @access  protected
	 * @return  void
	 */
	protected function unlock()
	{
		$this->_db->query(NULL, 'UNLOCK TABLES', TRUE);
	}

	/**
	 * Returns the value of the current nodes left column.
	 * 
	 * @access  public
	 * @return  integer
	 */
 	public function left()
	{
		return $this->{$this->left_column};
	}

	/**
	 * Returns the value of the current nodes right column.
	 * 
	 * @access  public
	 * @return  integer
	 */
	public function right()
	{
		return $this->{$this->right_column};
	}

	/**
	 * Returns the value of the current nodes level column.
	 * 
	 * @access  public
	 * @return  integer
	 */
	public function level()
	{
		return $this->{$this->level_column};
	}

	/**
	 * Returns the value of the current nodes scope column.
	 * 
	 * @access  public
	 * @return  integer
	 */
	public function scope()
	{
		return $this->{$this->scope_column};
	}

	/**
	 * Returns the size of the current node.
	 * 
	 * @access  public
	 * @return  integer
	 */
	public function size()
	{
		return $this->right() - $this->left() + 1;
	}

	/**
	 * Returns the number of descendants the current node has.
	 * 
	 * @access  public
	 * @return  integer
	 */
	public function count()
	{
		return ($this->size() - 2)/2;
	}

	/**
	 * Magic get function, maps field names to class functions.
	 * 
	 * @access  public
	 * @param   string  name of the field to get
	 * @return  mixed
	 */
	public function __get($column)
	{
		switch ($column)
		{
			case 'parent':
				return $this->parent();
			case 'parents':
				return $this->parents();
			case 'children':
				return $this->children();
			case 'first_child':
				return $this->children(FALSE, 'ASC', 1);
			case 'last_child':
				return $this->children(FALSE, 'DESC', 1);
			case 'siblings':
				return $this->siblings();
			case 'root':
				return $this->root();
			case 'roots':
				return $this->roots();
			case 'leaves':
				return $this->leaves();
			case 'descendants':
				return $this->descendants();
			case 'fulltree':
				return $this->fulltree();
			default:
				return parent::__get($column);
		}
	}

}