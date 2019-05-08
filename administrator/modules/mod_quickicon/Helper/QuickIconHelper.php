<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_quickicon
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\Quickicon\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Installer\Administrator\Model\ManageModel;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Helper for mod_quickicon
 *
 * @since  1.6
 */
abstract class QuickIconHelper
{
	/**
	 * Stack to hold buttons
	 *
	 * @var     array[]
	 * @since   1.6
	 */
	protected static $buttons = array();
	
	/**
	 * Helper method to return button list.
	 *
	 * This method returns the array by reference so it can be
	 * used to add custom buttons or remove default ones.
	 *
	 * @param   Registry        $params       The module parameters
	 * @param   CMSApplication  $application  The application
	 *
	 * @return  array  An array of buttons
	 *
	 * @since   1.6
	 */
	public static function &getButtons(Registry $params, CMSApplication $application = null)
	{
		if ($application == null)
		{
			$application = Factory::getApplication();
		}
$amount=1;
		$key = (string) $params;

		if (!isset(self::$buttons[$key]))
		{
			// Load mod_quickicon language file in case this method is called before rendering the module
			$application->getLanguage()->load('mod_quickicon');

			// Update Panel, icons come from plugins quickicons
			if ($params->get('icon_type', 'site') === 'update')
			{
				// Update Panel, icons come from plugins quickicons
				$context = $params->get('context', 'mod_quickicon');

				// Include buttons defined by published quickicon plugins
				PluginHelper::importPlugin('quickicon');

				$arrays = (array) $application->triggerEvent(
					'onGetIcons',
					new QuickIconsEvent('onGetIcons', ['context' => $context])
				);

				foreach ($arrays as $response)
				{
					foreach ($response as $icon)
					{
						$default = array(
							'link'   => null,
							'image'  => null,
							'text'   => null,
							'name'   => null,
							'addwhat'   => null,
							'linkadd'   => null,
							'access' => true,
							'class' => true,
							'group'  => 'MOD_QUICKICON_EXTENSIONS',
						);
						$icon = array_merge($default, $icon);

						if (!is_null($icon['link']) && !is_null($icon['text']))
						{
							self::$buttons[$key][] = $icon;
						}
					}
				}
			}
			elseif ($params->get('icon_type', 'site') === 'system')
			{
				// Load mod_quickicon language file in case this method is called before rendering the module
				$application->getLanguage()->load('mod_quickicon');
				
				if ($params->get('show_checkin', '1'))
				{
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_checkin&amp;task=getMenuBadgeData&amp;format=json',
						'link'   => Route::_('index.php?option=com_checkin'),
						'image'  => 'fa fa-unlock-alt',
						'name'   => Text::_('MOD_QUICKICON_CHECKINS'),
						'access' => array('core.admin', 'com_checkin'),
						'group'  => 'MOD_QUICKICON_SYSTEM'
					];
				}
				if ($params->get('show_cache', '1'))
				{
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_cache&amp;task=display.quickiconAmount&amp;format=json',
						'link'   => Route::_('index.php?option=com_cache'),
						'image'  => 'fa fa-cloud',
						'name'   => Text::_('MOD_QUICKICON_CACHE'),
						'access' => array('core.admin', 'com_cache'),
						'group'  => 'MOD_QUICKICON_SYTEM'
					];
				}
				if ($params->get('show_global', '1'))
				{				
					self::$buttons[$key][] = [
						'link'   => Route::_('index.php?option=com_config'),
						'image'  => 'fa fa-cog',
						'name'   => Text::_('MOD_QUICKICON_GLOBAL_CONFIGURATION'),
						'access' => array('core.manage', 'com_config', 'core.admin', 'com_config'),
						'group'  => 'MOD_QUICKICON_SYSTEM',
					];
				}
			}
			elseif ($params->get('icon_type', 'site') === 'site')
			{
				if ($params->get('show_users', '1'))
				{
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_users&amp;task=users.quickiconAmount&amp;format=json',
						'link'   => Route::_('index.php?option=com_users'),
						'image'  => 'fa fa-users',
						'linkadd'   => Route::_('index.php?option=com_users&task=user.add'),
						'addwhat' => Text::plural('MOD_QUICKICON_USER_MANAGER', 1),
						'name'   => Text::plural('MOD_QUICKICON_USER_MANAGER', $amount),
						'access' => array('core.manage', 'com_users', 'core.create', 'com_users'),
						'group'  => 'MOD_QUICKICON_SITE',
					];
				}

				if ($params->get('show_menuItems', '1'))
				{					
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_menus&amp;task=items.quickiconAmount&amp;format=json',
						'link'   => Route::_('index.php?option=com_menus'),						
						'image'  => 'fa fa-list',
						'linkadd'   => Route::_('index.php?option=com_menus&task=item.add'),
						'addwhat' => Text::plural('MOD_QUICKICON_MENUITEMS_MANAGER', 1),
						'name'   => Text::plural('MOD_QUICKICON_MENUITEMS_MANAGER', $amount),
						'access' => array('core.manage', 'com_menus', 'core.create', 'com_menus'),
						'group'  => 'MOD_QUICKICON_STRUCTURE',
					];
				}

				if ($params->get('show_articles', '1'))
				{
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_content&amp;task=articles.quickiconAmount&amp;format=json',
						'link'   => Route::_('index.php?option=com_content'),
						'image'  => 'fa fa-file-alt',
						'linkadd'   => Route::_('index.php?option=com_content&task=article.add'),
						'addwhat' => Text::plural('MOD_QUICKICON_ARTICLE_MANAGER', 1),
						'name'   => Text::plural('MOD_QUICKICON_ARTICLE_MANAGER', $amount),
						'access' => array('core.manage', 'com_content', 'core.create', 'com_content'),
						'group'  => 'MOD_QUICKICON_SITE',
					];
				}

				if ($params->get('show_categories', '1'))
				{
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_categories&amp;task=categories.quickiconAmount&amp;format=json',
						'link'   => Route::_('index.php?option=com_categories'),
						'image'  => 'fa fa-folder-open',
						'addwhat' => Text::plural('MOD_QUICKICON_CATEGORY_MANAGER', 1),
						'linkadd'   => Route::_('index.php?option=com_categories&task=category.add'),
						'name'   => Text::plural('MOD_QUICKICON_CATEGORY_MANAGER', $amount),
						'access' => array('core.manage', 'com_categories', 'core.create', 'com_categories'),
						'group'  => 'MOD_QUICKICON_SITE',
					];
				}

				if ($params->get('show_media', '1'))
				{
					self::$buttons[$key][] = [
						'image'  => 'fa fa-images',
						'link'   => Route::_('index.php?option=com_media'),
						'name'   => Text::_('MOD_QUICKICON_MEDIA_MANAGER'),
						'access' => array('core.manage', 'com_media'),
						'group'  => 'MOD_QUICKICON_SITE',
					];
				}

				if ($params->get('show_modules', '1'))
				{		
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_modules&amp;task=modules.quickiconAmount&amp;format=json',
						'link'   => Route::_('index.php?option=com_modules'),
						'image'  => 'fa fa-cube',
						'name'   => Text::plural('MOD_QUICKICON_MODULE_MANAGER', $amount),
						'addwhat' => Text::plural('MOD_QUICKICON_MODULE_MANAGER', 1),
						'linkadd'   => Route::_('index.php?option=com_categories&task=type.select'),
						'access' => array('core.manage', 'com_modules'),
						'group'  => 'MOD_QUICKICON_SITE'
					];
				}

				if ($params->get('show_plugins', '1'))
				{
					self::$buttons[$key][] = [
						'ajaxurl' => 'index.php?option=com_plugins&amp;task=plugins.quickiconAmount&amp;format=json',
						'link'   => Route::_('index.php?option=com_plugins'),
						'image'  => 'fa fa-plug',
						'name'   => Text::plural('MOD_QUICKICON_PLUGIN_MANAGER', $amount),
						'access' => array('core.manage', 'com_plugins'),
						'group'  => 'MOD_QUICKICON_SITE'
					];
				}

				if ($params->get('show_templates', '1'))
				{
					self::$buttons[$key][] = [
						'image'  => 'fa fa-paint-brush',
						'link'   => Route::_('index.php?option=com_templates&client_id=0'),
						'name'   => Text::_('MOD_QUICKICON_TEMPLATES'),
						'access' => array('core.admin', 'com_templates'),
						'group'  => 'MOD_QUICKICON_SITE'
					];
				}
			}
		}

		return self::$buttons[$key];
	}	
}
