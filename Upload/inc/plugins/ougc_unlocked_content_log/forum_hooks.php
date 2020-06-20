<?php

/***************************************************************************
 *
 *	OUGC Unlocked Content Log plugin (/inc/plugins/ougc_unlocked_content_log/forum_hooks.php)
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

namespace OUGCUnlockedContentLog\ForumHooks;

function global_start()
{
	global $templatelist, $mybb;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	if(defined('THIS_SCRIPT'))
	{
		if(THIS_SCRIPT == 'usercp.php')
		{
			$templatelist .= ',ougcunlockedcontentlog_usercp_nav';

			if($mybb->get_input('action') == 'unlocked_content')
			{
				$templatelist .= ',ougcunlockedcontentlog_content_empty, ougcunlockedcontentlog_content, ougcunlockedcontentlog';
			}
		}

		if(THIS_SCRIPT == 'modcp.php')
		{
			$templatelist .= ',ougcunlockedcontentlog_modcp_nav';

			if($mybb->get_input('action') == 'unlocked_content')
			{
				$templatelist .= ',ougcunlockedcontentlog_content_empty, ougcunlockedcontentlog_content, ougcunlockedcontentlog_filter_forum, ougcunlockedcontentlog_filter, ougcunlockedcontentlog';
			}
		}
	}
}

function usercp_start()
{
	modcp_start();
}

function modcp_start()
{
	global $mybb, $modcp_nav, $templates, $lang, $plugins, $usercpnav, $headerinclude, $header, $theme, $footer, $db, $gobutton, $cache, $parser;

	\OUGCUnlockedContentLog\Core\load_language();

	$modcp = $plugins->current_hook == 'modcp_start' ? true : false;

	if($modcp)
	{
		\OUGCUnlockedContentLog\Core\set_url('modcp.php');

		$permission = is_member($mybb->settings['ougc_unlocked_content_log_modgroups']);

		$uid = 0;
	}
	else
	{
		\OUGCUnlockedContentLog\Core\set_url('usercp.php');

		$permission = is_member($mybb->settings['ougc_unlocked_content_log_groups']);

		$uid = (int)$mybb->user['uid'];
	}

	$url = \OUGCUnlockedContentLog\Core\build_url();

	$where = $pids = [];

	$build_url = ['action' => 'unlocked_content'];

	if($permission)
	{
		if($modcp)
		{
			$nav = eval($templates->render('ougcunlockedcontentlog_modcp_nav'));

			$modcp_nav = str_replace('<!--OUGC_UNLOCKED_CONTENT_LOG-->', $nav, $modcp_nav);

			$navigation = &$modcp_nav;
		}
		else
		{
			$nav = eval($templates->render('ougcunlockedcontentlog_usercp_nav'));

			$usercpnav = str_replace('<!--OUGC_UNLOCKED_CONTENT_LOG-->', $nav, $usercpnav);

			$navigation = &$usercpnav;

			$where[] = "p.visible='1'";

			$where[] = "t.visible='1'";
		}
	}

	if($mybb->get_input('action') != 'unlocked_content')
	{
		return;
	}

	if(!$permission)
	{
		error_no_permission();
	}

	$perpage = (int)$mybb->settings['ougc_unlocked_content_logper_page'];

	if($modcp)
	{
		if($mybb->request_method == 'post')
		{
			$filter = $mybb->get_input('filter', \MyBB::INPUT_ARRAY);
	
			if(!empty($filter['username']))
			{
				$search_user = get_user_by_username($filter['username']);
	
				$build_url['uid'] = $uid = (int)$search_user['uid'];
			}
	
			if(!empty($filter['fid']))
			{
				$build_url['fid'] = (int)$filter['fid'];

				$where[] = "p.fid='{$build_url['fid']}'";
			}
		}
		else
		{
			if(!empty($mybb->input['uid']))
			{
				$build_url['uid'] = $uid = $mybb->get_input('uid', \MyBB::INPUT_INT);
			}

			if(!empty($mybb->input['fid']))
			{
				$build_url['fid'] = $mybb->get_input('fid', \MyBB::INPUT_INT);

				$where[] = "p.fid='{$build_url['fid']}'";
			}
		}
	}

	if(function_exists('hidecontent_info'))
	{
		$query = $db->simple_select('hidecontent', 'pid', "uid='{$uid}'");

		while($pids[] = (int)$db->fetch_field($query, 'pid'));
	}

	if(function_exists('lock_info'))
	{
		switch($db->type)
		{
			case 'pgsql':
			case 'sqlite':

				$query = $db->simple_select('posts', 'pid', "','||unlocked||',' LIKE '%,{$uid},%'");

				break;
			default:

				$query = $db->simple_select('posts', 'pid', "CONCAT(',',unlocked,',') LIKE '%,{$uid},%'");

				break;
		}

		while($pids[] = (int)$db->fetch_field($query, 'pid'));
	}

	$posts = '';

	$pids = array_filter(array_unique($pids));

	if($pids)
	{
		$pids = implode("','", $pids);

		$where[] = "p.pid IN ('{$pids}')";

		$query = $db->simple_select(
			'posts p LEFT JOIN '.$db->table_prefix.'threads t ON (t.tid=p.tid)',
			'COUNT(p.pid) AS total_posts',
			implode(' AND ', $where)
		);

		$total_posts = (int)$db->fetch_field($query, 'total_posts');

		if(!$total_posts)
		{
			$pids = false;
		}
	}

	if($pids)
	{
		$lang->load('search');

		$perpage = (int)$mybb->settings['ougc_unlocked_content_log_perpage'];

		if($perpage < 1)
		{
			$perpage = 10;
		}

		$page = $mybb->get_input('page', \MyBB::INPUT_INT);

		if($page > 0)
		{
			$start = ($page - 1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}

		$pages = ceil($total_posts / $perpage);

		if($page > $pages)
		{
			$start = 0;

			$page = 1;
		}

		$query = $db->simple_select(
			'posts p LEFT JOIN '.$db->table_prefix.'threads t ON (t.tid=p.tid) LEFT JOIN '.$db->table_prefix.'users u ON (u.uid=p.uid)',
			'p.*, u.username AS userusername, t.subject AS thread_subject, t.lastpost AS thread_lastpost, t.closed AS thread_closed, t.uid as thread_uid',
			implode(' AND ', $where),
			[
				'order_by' => 'p.dateline',
				'order_dir' => 'asc',
				'limit' => $perpage,
				'limit_start' => $start,
			]
		);
	
		$icon_cache = $cache->read('posticons');
	
		$forumcache = $cache->read('forums');

		if(!($parser instanceof postParser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';

			$parser = new \postParser;
		}

		while($post = $db->fetch_array($query))
		{
			$trow = alt_trow();

			if(isset($icon_cache[$post['icon']]))
			{
				$posticon = $icon_cache[$post['icon']];
	
				$posticon['name'] = htmlspecialchars_uni($posticon['name']);

				$posticon['path'] = str_replace('{theme}', $theme['imgdir'], $posticon['path']);

				$posticon['path'] = htmlspecialchars_uni($posticon['path']);

				$icon = eval($templates->render('ougcunlockedcontentlog_content_row_icon'));
			}
			else
			{
				$icon = '&nbsp;';
			}

			$thread_url = get_thread_link($post['tid']);

			$post_url = get_post_link($post['pid'], $post['tid']);

			$post['thread_subject'] = $parser->parse_badwords($post['thread_subject']);

			$post['thread_subject'] = htmlspecialchars_uni($post['thread_subject']);

			$post['subject'] = $parser->parse_badwords($post['subject']);

			$post['subject'] = htmlspecialchars_uni($post['subject']);

			if($post['userusername'])
			{
				$post['username'] = $post['userusername'];
			}

			$post['username'] = htmlspecialchars_uni($post['username']);

			$post['profilelink'] = build_profile_link($post['username'], $post['uid']);

			$post['forumlink'] = '';

			if($forumcache[$post['fid']])
			{
				$post['forumlink_link'] = get_forum_link($post['fid']);
	
				$post['forumlink_name'] = $forumcache[$post['fid']]['name'];

				$post['forumlink'] .= eval($templates->render('ougcunlockedcontentlog_content_row_forum'));
			}

			$posted = my_date('relative', $post['dateline']);

			$posts .= eval($templates->render('ougcunlockedcontentlog_content_row'));
		}

		$multipage = (string)multipage($total_posts, $perpage, $page, \OUGCUnlockedContentLog\Core\build_url($build_url));
	}

	if(!$posts)
	{
		$posts = eval($templates->render('ougcunlockedcontentlog_content_empty'));
	}

	$content = eval($templates->render('ougcunlockedcontentlog_content'));

	$modpanel = '';

	if($modcp)
	{
		$filter_username = '';

		if(!empty($filter['username']))
		{
			$filter_username = htmlspecialchars_uni($filter['username']);
		}

		$forums = build_forum_jump(0, $build_url['fid'], true, '', false, true, '', 'filter[fid]');

		$selected = '';

		if(!$build_url['fid'])
		{
			$selected = ' selected="selected"';
		}

		$additional = eval($templates->render('ougcunlockedcontentlog_filter_forum'));

		$forums = str_replace('</select>', $additional.'</select>', $forums);

		$modpanel = eval($templates->render('ougcunlockedcontentlog_filter'));
	}

	$page = eval($templates->render('ougcunlockedcontentlog'));

	output_page($page);
}