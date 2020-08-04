<?php

/***************************************************************************
 *
 *	OUGC Unlocked Content Log plugin (/inc/plugins/ougc_unlocked_content_log.php)
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
 
// Die if IN_MYBB is not defined, for security reasons.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

define('OUGC_UNLOCKED_CONTENT_LOG_ROOT', MYBB_ROOT . 'inc/plugins/ougc_unlocked_content_log');

require_once OUGC_UNLOCKED_CONTENT_LOG_ROOT.'/core.php';

// Add our hooks
if(defined('IN_ADMINCP'))
{
	require_once OUGC_UNLOCKED_CONTENT_LOG_ROOT.'/admin.php';
}
else
{
	require_once OUGC_UNLOCKED_CONTENT_LOG_ROOT.'/forum_hooks.php';

	\OUGCUnlockedContentLog\Core\addHooks('OUGCUnlockedContentLog\ForumHooks');
}

// Plugin API
function ougc_unlocked_content_log_info()
{
	return \OUGCUnlockedContentLog\Admin\_info();
}

// Activate the plugin.
function ougc_unlocked_content_log_activate()
{
	\OUGCUnlockedContentLog\Admin\_activate();
}

// Deactivate the plugin.
function ougc_unlocked_content_log_deactivate()
{
	\OUGCUnlockedContentLog\Admin\_deactivate();
}

// Check if installed.
function ougc_unlocked_content_log_is_installed()
{
	return \OUGCUnlockedContentLog\Admin\_is_installed();
}

// Unnstall the plugin.
function ougc_unlocked_content_log_uninstall()
{
	\OUGCUnlockedContentLog\Admin\_uninstall();
}