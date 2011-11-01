# This is the stable branch for Kohana 3.1 and is no longer maintained!!!

# MPTT Library, extends ORM

## Setup

Place module in /modules/ and include the call in your bootstrap.

## Declaring your ORM object

	class Model_Category extends ORM_MPTT {
	}


## Usage Examples

### Creating a root node:

	$cat = ORM::factory('Category_Mptt');
	$cat->name = 'Music';
	$cat->insert_as_new_root();
	echo 'Category ID'.$mptt->id.' set at level '.$cat->lvl.' (scope: '.$cat->scope.')';
	$c1 = $cat; // Saving id for next example

### Creating a child node:

	$cat->clear(); // Clearing ORM object
	$cat->name = 'Terminology';
	$cat->insert_as_last_child($c1);


