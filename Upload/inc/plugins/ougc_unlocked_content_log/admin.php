<?php

/***************************************************************************
 *
 *	OUGC Unlocked Content Log plugin (/inc/plugins/ougc_unlocked_content_log/admin.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2020 Omar Gonzalez
 *
 *	Website: https://ougc.network
 *
 *	Display unlocked content list in UserCP and ModCP for the Hide Content and OUGC Lock plugins.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

namespace OUGCUnlockedContentLog\Admin;

function _info()
{
	global $lang;

	\OUGCUnlockedContentLog\Core\load_language();

	return [
		'name'			=> 'OUGC Unlocked Content Log Rewards',
		'description'	=> $lang->setting_group_ougc_unlocked_content_log_desc,
		'website'		=> 'https://ougc.network',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'https://ougc.network',
		'version'		=> '1.8.0',
		'versioncode'	=> 1800,
		'compatibility'	=> '18*',
		'codename'		=> 'ougc_unlocked_content_log',
		'pl'			=> [
			'version'	=> 13,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
		]
	];
}

function _activate()
{
	global $PL, $lang, $cache;

	\OUGCUnlockedContentLog\Core\verify_pluginlibrary();

	$PL->settings('ougc_unlocked_content_log', $lang->setting_group_ougc_unlocked_content_log, $lang->setting_group_ougc_unlocked_content_log_desc, [
		'groups' => [
			'title' => $lang->setting_ougc_unlocked_content_log_groups,
			'description' => $lang->setting_ougc_unlocked_content_log_groups_desc,
			'optionscode' => 'groupselect',
			'value' =>	-1,
		],
		'modgroups' => [
			'title' => $lang->setting_ougc_unlocked_content_log_modgroups,
			'description' => $lang->setting_ougc_unlocked_content_log_modgroups_desc,
			'optionscode' => 'groupselect',
			'value' =>	4,
		],
		'perpage' => [
			'title' => $lang->setting_ougc_unlocked_content_log_perpage,
			'description' => $lang->setting_ougc_unlocked_content_log_perpage_desc,
			'optionscode' => 'numeric',
			'value' =>	10,
		],
	]);

	// Add templates
    $templatesDirIterator = new \DirectoryIterator(OUGC_UNLOCKED_CONTENT_LOG_ROOT.'/templates');

	$templates = [];

    foreach($templatesDirIterator as $template)
    {
		if(!$template->isFile())
		{
			continue;
		}

		$pathName = $template->getPathname();

        $pathInfo = pathinfo($pathName);

		if($pathInfo['extension'] === 'html')
		{
            $templates[$pathInfo['filename']] = file_get_contents($pathName);
		}
    }

	if($templates)
	{
		$PL->templates('ougcunlockedcontentlog', 'OUGC Unlocked Content Log', $templates);
	}

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');

	if(!$plugins)
	{
		$plugins = [];
	}

	$_info = \OUGCUnlockedContentLog\Admin\_info();

	if(!isset($plugins['unlockedcontentlog']))
	{
		$plugins['unlockedcontentlog'] = $_info['versioncode'];
	}

	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

	find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch}<!--OUGC_UNLOCKED_CONTENT_LOG-->');
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('{$attachmentop}').'#', '{$attachmentop}<!--OUGC_UNLOCKED_CONTENT_LOG-->');

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['unlockedcontentlog'] = $_info['versioncode'];

	$cache->update('ougc_plugins', $plugins);
}

function _deactivate()
{
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

	find_replace_templatesets('modcp_nav_users', '#'.preg_quote('<!--OUGC_UNLOCKED_CONTENT_LOG-->').'#i', '', 0);
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('<!--OUGC_UNLOCKED_CONTENT_LOG-->').'#i', '', 0);
}

function _install()
{
}

function _is_installed()
{
	global $cache;

	$plugins = $cache->read('ougc_plugins');

	if(!$plugins)
	{
		$plugins = [];
	}

	return isset($plugins['unlockedcontentlog']);
}

function _uninstall()
{
	global $db, $PL, $cache;

	\OUGCUnlockedContentLog\Core\verify_pluginlibrary();

	$PL->settings_delete('ougc_unlocked_content_log');

	$PL->templates_delete('ougcunlockedcontentlog');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['unlockedcontentlog']))
	{
		unset($plugins['unlockedcontentlog']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}