<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tags
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Tags\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Nested;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

/**
 * Tags table
 *
 * @since  3.1
 */
class TagTable extends Nested
{
	/**
	 * An array of key names to be json encoded in the bind function
	 *
	 * @var    array
	 * @since  4.0.0
	 */
	protected $_jsonEncode = ['params', 'metadata', 'urls', 'images'];

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $_supportNullValue = true;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = 'com_tags.tag';

		parent::__construct('#__tags', 'id', $db);
	}

	/**
	 * Overloaded check method to ensure data integrity.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 * @throws  \UnexpectedValueException
	 */
	public function check()
	{
		try
		{
			parent::check();
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		// Check for valid name.
		if (trim($this->title) == '')
		{
			throw new \UnexpectedValueException(sprintf('The title is empty'));
		}

		if (empty($this->alias))
		{
			$this->alias = $this->title;
		}

		$this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
		}

		// Check the publish down date is not earlier than publish up.
		if (!empty($this->publish_down) && !empty($this->publish_up) && $this->publish_down < $this->publish_up)
		{
			throw new \UnexpectedValueException(sprintf('End publish date is before start publish date.'));
		}

		// Clean up description -- eliminate quotes and <> brackets
		if (!empty($this->metadesc))
		{
			// Only process if not empty
			$bad_characters = array("\"", '<', '>');
			$this->metadesc = StringHelper::str_ireplace($bad_characters, '', $this->metadesc);
		}

		if (empty($this->params))
		{
			$this->params = '{}';
		}

		if (empty($this->metadesc))
		{
			$this->metadesc = '';
		}

		if (empty($this->metakey))
		{
			$this->metakey = '';
		}

		if (empty($this->metadata))
		{
			$this->metadata = '{}';
		}

		if (empty($this->urls))
		{
			$this->urls = '{}';
		}

		if (empty($this->images))
		{
			$this->images = '{}';
		}

		if (!(int) $this->checked_out_time)
		{
			$this->checked_out_time = null;
		}

		if (!(int) $this->publish_up)
		{
			$this->publish_up = null;
		}

		if (!(int) $this->publish_down)
		{
			$this->publish_down = null;
		}

		return true;
	}

	/**
	 * Overridden \JTable::store to set modified data and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function store($updateNulls = true)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		if ($this->id)
		{
			// Existing item
			$this->modified_user_id = $user->get('id');
			$this->modified_time = $date->toSql();
		}
		else
		{
			// New tag. A tag created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!(int) $this->created_time)
			{
				$this->created_time = $date->toSql();
			}

			if (empty($this->created_user_id))
			{
				$this->created_user_id = $user->get('id');
			}

			if (!(int) $this->modified_time)
			{
				$this->modified_time = $this->created_time;
			}

			if (empty($this->modified_user_id))
			{
				$this->modified_user_id = $this->created_user_id;
			}
		}

		// Verify that the alias is unique
		$table = new static($this->getDbo());

		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(Text::_('COM_TAGS_ERROR_UNIQUE_ALIAS'));

			return false;
		}

		return parent::store($updateNulls);
	}

	/**
	 * Method to delete a node and, optionally, its child nodes from the table.
	 *
	 * @param   integer  $pk        The primary key of the node to delete.
	 * @param   boolean  $children  True to delete child nodes, false to move them up a level.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function delete($pk = null, $children = false)
	{
		$return = parent::delete($pk, $children);

		if ($return)
		{
			$helper = new TagsHelper;
			$helper->tagDeleteInstances($pk);
		}

		return $return;
	}
}
