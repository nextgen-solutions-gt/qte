<?php
/**
 *
 * Quick Title Edition extension for the phpBB Forum Software package
 *
 * @copyright (c) 2023, Kailey Snay, https://www.snayhomelab.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kaileymsnay\qte\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Quick Title Edition event listener
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $table_prefix;

	/** @var \kaileymsnay\qte\qte */
	protected $qte;

	/** @var kaileymsnay\qte\search\fulltext_attribute */
	protected $qte_search;

	/** @var bool */
	private $search_attr = false;

	/** @var int */
	private $search_attr_id;

	/**
	 * Constructor
	 *
	 * @param \phpbb\cache\driver\driver_interface        $cache
	 * @param \phpbb\db\driver\driver_interface           $db
	 * @param \phpbb\language\language                    $language
	 * @param \phpbb\log\log                              $log
	 * @param \phpbb\request\request                      $request
	 * @param \phpbb\template\template                    $template
	 * @param \phpbb\user                                 $user
	 * @param string                                      $table_prefix
	 * @param \kaileymsnay\qte\qte                        $qte
	 * @param \kaileymsnay\qte\search\fulltext_attribute  $qte_search
	 */
	public function __construct(\phpbb\cache\driver\driver_interface $cache, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $table_prefix, \kaileymsnay\qte\qte $qte, \kaileymsnay\qte\search\fulltext_attribute $qte_search)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->table_prefix = $table_prefix;
		$this->qte = $qte;
		$this->qte_search = $qte_search;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'	=> 'user_setup',
			'core.permissions'	=> 'add_permissions',

			'core.display_forums_modify_forum_rows'		=> 'display_forums_modify_forum_rows',
			'core.display_forums_modify_row'			=> 'display_forums_modify_row',
			'core.display_forums_modify_sql'			=> 'display_forums_modify_sql',
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',

			'core.display_user_activity_modify_actives'	=> 'display_user_activity_modify_actives',

			// ACP
			'core.delete_user_after'	=> 'delete_user_after',

			'core.get_logs_main_query_before'	=> 'get_logs_main_query_before',
			'core.get_logs_modify_entry_data'	=> 'get_logs_modify_entry_data',

			'core.acp_manage_forums_display_form'		=> 'acp_manage_forums_display_form',
			'core.acp_manage_forums_initialise_data'	=> 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_request_data'		=> 'acp_manage_forums_request_data',
			'core.acp_manage_forums_update_data_after'	=> 'acp_manage_forums_update_data_after',

			// MCP
			'core.mcp_forum_view_before'			=> 'mcp_forum_view_before',
			'core.mcp_main_modify_fork_sql'			=> 'mcp_main_modify_fork_sql',
			'core.mcp_main_modify_shadow_sql'		=> 'mcp_main_modify_shadow_sql',
			'core.mcp_view_forum_modify_topicrow'	=> 'mcp_view_forum_modify_topicrow',

			// Posting
			'core.posting_modify_submission_errors'		=> 'posting_modify_submission_errors',
			'core.posting_modify_submit_post_before'	=> 'posting_modify_submit_post_before',
			'core.posting_modify_template_vars'			=> 'posting_modify_template_vars',
			'core.submit_post_modify_sql_data'			=> 'submit_post_modify_sql_data',

			// Search
			'core.search_backend_search_after'		=> 'search_backend_search_after',
			'core.search_modify_forum_select_list'	=> 'search_modify_forum_select_list',
			'core.search_modify_submit_parameters'	=> 'search_modify_submit_parameters',
			'core.search_modify_tpl_ary'			=> 'search_modify_tpl_ary',
			'core.search_modify_url_parameters'		=> 'search_modify_url_parameters',

			// Seach backends
			'core.search_mysql_author_query_before'			=> 'search_author_query_before',
			'core.search_mysql_by_author_modify_search_key'	=> 'search_by_author_modify_search_key',
			'core.search_mysql_keywords_main_query_before'	=> 'search_keywords_main_query_before',

			'core.search_native_author_count_query_before'		=> 'search_author_query_before',
			'core.search_native_by_author_modify_search_key'	=> 'search_by_author_modify_search_key',
			'core.search_native_keywords_count_query_before'	=> 'search_keywords_main_query_before',

			'core.search_postgres_author_count_query_before'	=> 'search_author_query_before',
			'core.search_postgres_by_author_modify_search_key'	=> 'search_by_author_modify_search_key',
			'core.search_postgres_keywords_main_query_before'	=> 'search_keywords_main_query_before',

			// UCP (bookmarks and subscriptions)
			'core.ucp_main_topiclist_topic_modify_template_vars'	=> 'ucp_main_topiclist_topic_modify_template_vars',

			// Viewforum
			'core.viewforum_modify_topicrow'	=> 'viewforum_modify_topicrow',
			'core.viewforum_modify_topics_data'	=> 'viewforum_modify_topics_data',

			// Viewtopic
			'core.viewtopic_add_quickmod_option_before'		=> 'viewtopic_add_quickmod_option_before',
			'core.viewtopic_assign_template_vars_before'	=> 'viewtopic_assign_template_vars_before',
			'core.viewtopic_modify_page_title'				=> 'viewtopic_modify_page_title',
		];
	}

	/**
	 * Load common language files
	 */
	public function user_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'kaileymsnay/qte',
			'lang_set' => 'attributes',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Add permissions to the ACP -> Permissions settings page
	 * This is where permissions are assigned language keys and
	 * categories (where they will appear in the Permissions table):
	 * actions|content|forums|misc|permissions|pm|polls|post
	 * post_actions|posting|profile|settings|topic_actions|user_group
	 */
	public function add_permissions($event)
	{
		$event->update_subarray('categories', 'qte', 'ACL_CAT_QTE');

		$permissions = [
			'a_attr_manage'		=> ['lang' => 'ACL_A_ATTR_MANAGE', 'cat' => 'posting'],
			'm_qte_attr_edit'	=> ['lang' => 'ACL_M_QTE_ATTR_EDIT', 'cat' => 'qte'],
			'm_qte_attr_delete'	=> ['lang' => 'ACL_M_QTE_ATTR_DELETE', 'cat' => 'qte'],
		];

		// Add a_ and m_ permissions
		foreach ($permissions as $key => $value)
		{
			$event->update_subarray('permissions', $key, $value);
		}

		// Add QTE specific permissions
		foreach ($this->qte->get_attr() as $attr)
		{
			$event->update_subarray('permissions', 'f_qte_attr_' . $attr['attr_id'], ['lang' => $this->language->lang('QTE_CAN_USE_ATTR', $attr['attr_name']), 'cat' => 'qte']);
		}
	}

	public function display_forums_modify_forum_rows($event)
	{
		$forum_rows = $event['forum_rows'];
		$parent_id = $event['parent_id'];
		$row = $event['row'];

		// Suggested by the core, equal post times should never happen. Check it just in case.
		if ($row['forum_last_post_time'] >= $forum_rows[$parent_id]['forum_last_post_time'])
		{
			$forum_rows[$parent_id]['topic_attr_id'] = $row['topic_attr_id'];

			// Is topic_attr_id not null?
			if ($forum_rows[$parent_id]['topic_attr_id'] !== false)
			{
				$forum_rows[$parent_id]['topic_attr_id'] = $row['topic_attr_id'];
			}
		}

		$event['forum_rows'] = $forum_rows;
	}

	public function display_forums_modify_row($event)
	{
		$sql = 'SELECT topic_id
			FROM ' . $this->table_prefix . 'posts
			WHERE post_id = ' . (int) $event['row']['forum_last_post_id'];
		$result = $this->db->sql_query($sql);
		$topic_list = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$topic_list[] = (int) $row['topic_id'];
		}
		$this->db->sql_freeresult($result);

		$event->update_subarray('row', 'topic_id', $topic_list);
	}

	public function display_forums_modify_sql($event)
	{
		$sql_ary = $event['sql_ary'];

		$sql_ary['SELECT'] .= ', t.topic_attr_id, t.topic_attr_user, t.topic_attr_time';
		$sql_ary['LEFT_JOIN'][] = [
			'FROM'	=> [$this->table_prefix . 'posts' => 'p'],
			'ON'	=> 'f.forum_last_post_id = p.post_id',
		];
		$sql_ary['LEFT_JOIN'][] = [
			'FROM'	=> [$this->table_prefix . 'topics' => 't'],
			'ON'	=> 't.topic_id = p.topic_id',
		];

		$event['sql_ary'] = $sql_ary;
	}

	public function display_forums_modify_template_vars($event)
	{
		$this->qte->get_users_by_topic_id($event['row']['topic_id']);
		$event->update_subarray('forum_row', 'TOPIC_ATTRIBUTE', $this->qte->attr_display($event['row']['topic_attr_id'], $event['row']['topic_attr_user'], $event['row']['topic_attr_time']));
	}

	public function display_user_activity_modify_actives($event)
	{
		if (isset($event['active_t_row']['topic_id']))
		{
			$sql = 'SELECT topic_attr_id, topic_attr_user, topic_attr_time
				FROM ' . $this->table_prefix . 'topics
				WHERE topic_id = ' . (int) $event['active_t_row']['topic_id'];
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$topic_list = [];
			$topic_list[] = $event['active_t_row']['topic_id'];

			$this->qte->get_users_by_topic_id($topic_list);
			$this->template->assign_var('TOPIC_ATTRIBUTE', $this->qte->attr_display($row['topic_attr_id'], $row['topic_attr_user'], $row['topic_attr_time']));
		}
	}

	// ACP
	public function delete_user_after($event)
	{
		$sql = 'UPDATE ' . $this->table_prefix . 'topics
			SET topic_attr_user = ' . ANONYMOUS . '
			WHERE ' . $this->db->sql_in_set('topic_attr_user', $event['user_ids']);
		$this->db->sql_query($sql);
	}

	public function get_logs_main_query_before($event)
	{
		$this->language->add_lang(['attributes', 'logs_attributes'], 'kaileymsnay/qte');
	}

	public function get_logs_modify_entry_data($event)
	{
		$row = $event['row'];

		if (strpos($row['log_operation'], 'LOG_ATTRIBUTE_') === 0 || strpos($row['log_operation'], 'MCP_ATTRIBUTE_') === 0)
		{
			$log_data = unserialize($row['log_data']);

			if (!empty($log_data) && is_array($log_data))
			{
				foreach ($log_data as &$arg)
				{
					$arg = str_replace(['%mod%', '%date%'], [$this->language->lang('QTE_KEY_USERNAME'), $this->language->lang('QTE_KEY_DATE')], $this->language->lang($arg));
				}
			}

			$row['log_data'] = serialize($log_data);

			$event['row'] = $row;
		}
	}

	public function acp_manage_forums_display_form($event)
	{
		$this->language->add_lang('attributes_acp', 'kaileymsnay/qte');

		if ($event['action'] == 'edit')
		{
			$this->qte->attr_default($event['forum_id'], $event['forum_data']['default_attr']);
		}

		$event->update_subarray('template_data', 'S_FORCE_ATTR', $event['forum_data']['force_attr']);
	}

	public function acp_manage_forums_initialise_data($event)
	{
		if ($event['action'] == 'add')
		{
			$event->update_subarray('forum_data', 'default_attr', 0);
			$event->update_subarray('forum_data', 'force_attr', false);
		}
	}

	public function acp_manage_forums_request_data($event)
	{
		$event->update_subarray('forum_data', 'default_attr', $this->request->variable('default_attr', 0));
		$event->update_subarray('forum_data', 'force_attr', $this->request->variable('force_attr', false));
	}

	public function acp_manage_forums_update_data_after($event)
	{
		if (!count($event['errors']))
		{
			$from_attr = $this->request->variable('from_attr', 0);

			if ($from_attr && $from_attr != $event['forum_data']['forum_id'])
			{
				$this->_copy_attribute_permissions($from_attr, $event['forum_data']['forum_id'], $event['is_new_forum'] ? false : true);
			}
		}
	}

	// MCP
	public function mcp_forum_view_before($event)
	{
		$attr_id = (int) $this->request->variable('attr_id', 0);
		$forum_id = (int) $event['forum_info']['forum_id'];

		if ($attr_id)
		{
			$this->qte->mcp_attr_apply($attr_id, $forum_id, $event['topic_id_list']);
		}

		$this->qte->attr_select($forum_id);
	}

	public function mcp_main_modify_fork_sql($event)
	{
		$data = [
			'topic_attr_id'		=> $event['topic_row']['topic_attr_id'],
			'topic_attr_user'	=> $event['topic_row']['topic_attr_user'],
			'topic_attr_time'	=> $event['topic_row']['topic_attr_time'],
		];

		foreach ($data as $key => $value)
		{
			$event->update_subarray('sql_ary', $key, $value);
		}
	}

	public function mcp_main_modify_shadow_sql($event)
	{
		$data = [
			'topic_attr_id'		=> $event['row']['topic_attr_id'],
			'topic_attr_user'	=> $event['row']['topic_attr_user'],
			'topic_attr_time'	=> $event['row']['topic_attr_time'],
		];

		foreach ($data as $key => $value)
		{
			$event->update_subarray('shadow', $key, $value);
		}
	}

	public function mcp_view_forum_modify_topicrow($event)
	{
		if (!empty($event['row']['topic_attr_id']))
		{
			$this->qte->get_users_by_user_id($event['row']['topic_attr_user']);

			$event->update_subarray('topic_row', 'MCP_TOPIC_ATTRIBUTE', $this->qte->attr_display($event['row']['topic_attr_id'], $event['row']['topic_attr_user'], $event['row']['topic_attr_time']));
		}
	}

	// Posting
	public function posting_modify_submission_errors($event)
	{
		$post_data = $event['post_data'];
		$post_data['attr_id'] = $this->request->variable('attr_id', \kaileymsnay\qte\qte::KEEP, false, \phpbb\request\request_interface::POST);

		if ($event['post_data']['force_attr'])
		{
			if ((!$post_data['attr_id'] || $post_data['attr_id'] == \kaileymsnay\qte\qte::DELETE) && ($event['mode'] == 'post' || ($event['mode'] == 'edit' && $event['post_data']['topic_first_post_id'] == $event['post_id'])))
			{
				$error = $event['error'];
				$error[] = $this->language->lang('QTE_ATTRIBUTE_UNSELECTED');
				$event['error'] = $error;

				// Init the value
				$post_data['attr_id'] = 0;
			}
		}

		$event['post_data'] = $post_data;
	}

	public function posting_modify_submit_post_before($event)
	{
		$topic_attribute = $event['post_data']['attr_id'];

		if ($event['mode'] != 'post' && $topic_attribute == $event['post_data']['topic_attr_id'])
		{
			$topic_attribute = \kaileymsnay\qte\qte::KEEP;
		}
		else if (empty($event['post_data']['topic_attr_id']) && $topic_attribute == \kaileymsnay\qte\qte::DELETE)
		{
			$topic_attribute = \kaileymsnay\qte\qte::KEEP;
		}

		$event->update_subarray('data', 'attr_id', (int) (($event['mode'] == 'post') && !empty($event['post_data']['default_attr'])) ? $event['post_data']['default_attr'] : $topic_attribute);
	}

	public function posting_modify_template_vars($event)
	{
		$topic_attribute = $this->request->variable('attr_id', !empty($event['post_data']['topic_attr_id']) ? \kaileymsnay\qte\qte::KEEP : 0, false, \phpbb\request\request_interface::POST);

		$this->qte->attr_select($event['forum_id'], !empty($event['post_data']['topic_poster']) ? $event['post_data']['topic_poster'] : 0, (int) $topic_attribute, '', $event['mode']);

		if ($event['mode'] != 'post')
		{
			$post_data = $event['post_data'];

			if ($topic_attribute != \kaileymsnay\qte\qte::KEEP)
			{
				$post_data['topic_attr_id']		= (int) $topic_attribute;
				$post_data['topic_attr_user']	= (int) $this->user->data['user_id'];
				$post_data['topic_attr_time']	= time();

				$this->qte->get_users_by_user_id($this->user->data['user_id']);
			}

			if ($topic_attribute != \kaileymsnay\qte\qte::DELETE)
			{
				$this->qte->get_users_by_topic_id([$post_data['topic_id']]);
				$this->template->assign_var('TOPIC_ATTRIBUTE', $this->qte->attr_display($post_data['topic_attr_id'], $post_data['topic_attr_user'], $post_data['topic_attr_time']));
			}
		}
	}

	public function submit_post_modify_sql_data($event)
	{
		if (isset($event['data']['attr_id']) && $event['data']['attr_id'] != \kaileymsnay\qte\qte::KEEP)
		{
			$sql_data = $event['sql_data'];

			if ($event['data']['attr_id'] == \kaileymsnay\qte\qte::DELETE)
			{
				$sql_data[TOPICS_TABLE]['stat'][] = 'topic_attr_id = 0';
				$sql_data[TOPICS_TABLE]['stat'][] = 'topic_attr_user = 0';
				$sql_data[TOPICS_TABLE]['stat'][] = 'topic_attr_time = 0';
			}
			else
			{
				$sql_data[TOPICS_TABLE]['stat'][] = 'topic_attr_id = ' . (int) $event['data']['attr_id'];
				$sql_data[TOPICS_TABLE]['stat'][] = 'topic_attr_user = ' . (int) $this->user->data['user_id'];
				$sql_data[TOPICS_TABLE]['stat'][] = 'topic_attr_time = ' . time();
			}

			$event['sql_data'] = $sql_data;

			if (in_array($event['post_mode'], ['edit_topic', 'edit_first_post']))
			{
				$attr_name = ($event['data']['attr_id'] != \kaileymsnay\qte\qte::DELETE) ? $this->qte->get_attr_name_by_id($event['data']['attr_id']) : '';

				$log_data = [
					'forum_id'	=> $event['data']['forum_id'],
					'topic_id'	=> $event['data']['topic_id'],
					$attr_name,
				];

				$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, $this->language->lang('MCP_ATTRIBUTE_' . ($event['data']['attr_id'] == \kaileymsnay\qte\qte::DELETE ? 'DELETED' : 'UPDATED')), time(), $log_data);
			}
		}
	}

	// Search
	public function search_backend_search_after($event)
	{
		if ($this->search_attr)
		{
			$keywords = utf8_normalize_nfc($this->request->variable('keywords', '', true));
			$author = $this->request->variable('author', '', true);

			if (!$keywords && !$author)
			{
				$id_ary = $event['id_ary'];
				$start = $event['start'];
				$total_match_count = $event['total_match_count'];

				$total_match_count = $this->qte_search->attribute_search($this->search_attr_id, $event['show_results'], $event['search_terms'], $event['sort_by_sql'], $event['sort_key'], $event['sort_dir'], $event['sort_days'], $event['ex_fid_ary'], $event['m_approve_posts_fid_sql'], $event['topic_id'], $event['author_id_ary'], $event['sql_author_match'], $id_ary, $start, $event['per_page']);

				$event['total_match_count'] = $total_match_count;
				$event['start'] = $start;
				$event['id_ary'] = $id_ary;
			}
		}
	}

	public function search_modify_forum_select_list($event)
	{
		$this->qte->attr_search();
	}

	public function search_modify_submit_parameters($event)
	{
		$topic_attribute = $this->request->variable('attr_id', 0, false, \phpbb\request\request_interface::GET);

		if ($topic_attribute)
		{
			$this->search_attr = true;
			$this->search_attr_id = $topic_attribute;

			$event['submit'] = true;
		}
	}

	public function search_modify_tpl_ary($event)
	{
		if (!empty($event['row']['topic_attr_id']))
		{
			$this->qte->get_users_by_topic_id((array) $event['row']['topic_id']);

			$event->update_subarray('tpl_ary', 'TOPIC_ATTRIBUTE', $this->qte->attr_display($event['row']['topic_attr_id'], $event['row']['topic_attr_user'], $event['row']['topic_attr_time']));
		}
	}

	public function search_modify_url_parameters($event)
	{
		if ($this->search_attr)
		{
			$event['u_search'] .= '&amp;attr_id=' . $this->search_attr_id;
		}
	}

	// Seach backends
	public function search_author_query_before($event)
	{
		if ($this->search_attr)
		{
			$event['sql_author'] .= ' AND t.topic_attr_id = ' . $this->search_attr_id;
		}
	}

	public function search_by_author_modify_search_key($event)
	{
		if ($this->search_attr)
		{
			$event['firstpost_only'] = true;
		}
	}

	public function search_keywords_main_query_before($event)
	{
		if ($this->search_attr)
		{
			// Fulltext_native
			if (isset($event['sql_where']))
			{
				$event['left_join_topics'] = true;

				$sql_where = $event['sql_where'];
				$sql_where[] = 't.topic_attr_id = ' . (int) $this->search_attr_id;
				$event['sql_where'] = $sql_where;
			}
			else
			{
				$event['join_topic'] = true;
				$event['sql_match_where'] = ' AND t.topic_attr_id = ' . (int) $this->search_attr_id;
			}
		}
	}

	// UCP (bookmarks and subscriptions)
	public function ucp_main_topiclist_topic_modify_template_vars($event)
	{
		if (!empty($event['row']['topic_attr_id']))
		{
			$event->update_subarray('template_vars', 'TOPIC_ATTRIBUTE', $this->qte->attr_display($event['row']['topic_attr_id'], $event['row']['topic_attr_user'], $event['row']['topic_attr_time']));
		}
	}

	// Viewforum
	public function viewforum_modify_topicrow($event)
	{
		if (!empty($event['row']['topic_attr_id']))
		{
			$event->update_subarray('topic_row', 'TOPIC_ATTRIBUTE', $this->qte->attr_display($event['row']['topic_attr_id'], $event['row']['topic_attr_user'], $event['row']['topic_attr_time']));
		}
	}

	public function viewforum_modify_topics_data($event)
	{
		if (count($event['topic_list']))
		{
			$this->qte->get_users_by_topic_id($event['topic_list']);
		}
	}

	// Viewtopic
	public function viewtopic_add_quickmod_option_before($event)
	{
		$attr_id = (int) $this->request->variable('attr_id', 0);

		if ($attr_id)
		{
			$this->qte->get_users_by_user_id($this->user->data['user_id']);
			$this->qte->attr_apply($attr_id, $event['topic_id'], $event['forum_id'], $event['topic_data']['topic_attr_id'], $event['topic_data']['topic_poster'], $event['viewtopic_url']);
		}
	}

	public function viewtopic_assign_template_vars_before($event)
	{
		if (!empty($event['topic_data']['topic_attr_id']))
		{
			$this->qte->get_users_by_topic_id([$event['topic_data']['topic_id']]);
			$this->template->assign_var('TOPIC_ATTRIBUTE', $this->qte->attr_display($event['topic_data']['topic_attr_id'], $event['topic_data']['topic_attr_user'], $event['topic_data']['topic_attr_time']));
		}

		$this->qte->attr_select($event['forum_id'], $event['topic_data']['topic_poster'], (int) $event['topic_data']['topic_attr_id'], $event['viewtopic_url']);
	}

	public function viewtopic_modify_page_title($event)
	{
		$attr_title = $this->qte->attr_title($event['topic_data']['topic_attr_id'], $event['topic_data']['topic_attr_user'], $event['topic_data']['topic_attr_time']);

		$event['page_title'] = $attr_title ? $attr_title . ' ' . $event['page_title'] : $event['page_title'];
	}

	/**
	 * Copies attributes permissions from one forum to others
	 */
	private function _copy_attribute_permissions($src_forum_id, $dest_forum_ids, $clear_dest_perms)
	{
		// Only one forum id specified
		if (!is_array($dest_forum_ids))
		{
			$dest_forum_ids = [$dest_forum_ids];
		}

		// Make sure forum ids are integers
		$src_forum_id = (int) $src_forum_id;
		$dest_forum_ids = array_map('intval', $dest_forum_ids);

		// No source forum or no destination forums specified
		if (empty($src_forum_id) || empty($dest_forum_ids))
		{
			return false;
		}

		// Check if source forum exists
		$sql = 'SELECT forum_name
			FROM ' . $this->table_prefix . 'forums
			WHERE forum_id = ' . (int) $src_forum_id;
		$result = $this->db->sql_query($sql);
		$src_forum_name = $this->db->sql_fetchfield('forum_name');
		$this->db->sql_freeresult($result);

		// Source forum doesn't exist
		if (empty($src_forum_name))
		{
			return false;
		}

		// Check if destination forums exists
		$sql = 'SELECT forum_id, forum_name
			FROM ' . $this->table_prefix . 'forums
			WHERE ' . $this->db->sql_in_set('forum_id', $dest_forum_ids);
		$result = $this->db->sql_query($sql);
		$dest_forum_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$dest_forum_ids[] = (int) $row['forum_id'];
		}
		$this->db->sql_freeresult($result);

		// No destination forum exists
		if (empty($dest_forum_ids))
		{
			return false;
		}

		// Get informations about acl options
		$sql = 'SELECT auth_option_id FROM ' . $this->table_prefix . 'acl_options
			WHERE auth_option ' . $this->db->sql_like_expression($this->db->get_any_char() . '_qte_attr_' . $this->db->get_any_char());
		$result = $this->db->sql_query($sql);

		$acl_options_ids = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$acl_options_ids[]	= (int) $row['auth_option_id'];
		}

		// No destination forum exists
		if (empty($dest_forum_ids))
		{
			return false;
		}

		// Get informations about acl options
		$sql = 'SELECT auth_option_id
			FROM ' . $this->table_prefix . 'acl_options
			WHERE auth_option ' . $this->db->sql_like_expression($this->db->get_any_char() . '_qte_attr_' . $this->db->get_any_char());
		$result = $this->db->sql_query($sql);
		$acl_options_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$acl_options_ids[] = (int) $row['auth_option_id'];
		}

		// Rowsets we're going to insert
		$users_sql_ary = $groups_sql_ary = [];

		// Query acl users table for source forum data
		$sql = 'SELECT user_id, auth_option_id, auth_role_id, auth_setting
			FROM ' . $this->table_prefix . 'acl_users
			WHERE ' . $this->db->sql_in_set('auth_option_id', $acl_options_ids) . '
				AND forum_id = ' . $src_forum_id;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$row = [
				'user_id'			=> (int) $row['user_id'],
				'auth_option_id'	=> (int) $row['auth_option_id'],
				'auth_role_id'		=> (int) $row['auth_role_id'],
				'auth_setting'		=> (int) $row['auth_setting'],
			];

			foreach ($dest_forum_ids as $dest_forum_id)
			{
				$users_sql_ary[] = $row + ['forum_id' => $dest_forum_id];
			}
		}
		$this->db->sql_freeresult($result);

		// Query acl groups table for source forum data
		$sql = 'SELECT group_id, auth_option_id, auth_role_id, auth_setting
			FROM ' . $this->table_prefix . 'acl_groups
			WHERE ' . $this->db->sql_in_set('auth_option_id', $acl_options_ids) . '
				AND forum_id = ' . $src_forum_id;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$row = [
				'group_id'			=> (int) $row['group_id'],
				'auth_option_id'	=> (int) $row['auth_option_id'],
				'auth_role_id'		=> (int) $row['auth_role_id'],
				'auth_setting'		=> (int) $row['auth_setting'],
			];

			foreach ($dest_forum_ids as $dest_forum_id)
			{
				$groups_sql_ary[] = $row + ['forum_id' => $dest_forum_id];
			}
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');

		if ($clear_dest_perms)
		{
			// Clear current permissions of destination forums
			$sql = 'DELETE FROM ' . $this->table_prefix . 'acl_users
				WHERE ' . $this->db->sql_in_set('auth_option_id', $acl_options_ids) . '
					AND ' . $this->db->sql_in_set('forum_id', $dest_forum_ids);
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->table_prefix . 'acl_groups
				WHERE ' . $this->db->sql_in_set('auth_option_id', $acl_options_ids) . '
					AND ' . $this->db->sql_in_set('forum_id', $dest_forum_ids);
			$this->db->sql_query($sql);
		}

		$this->db->sql_multi_insert($this->table_prefix . 'acl_users', $users_sql_ary);
		$this->db->sql_multi_insert($this->table_prefix . 'acl_groups', $groups_sql_ary);

		$this->db->sql_transaction('commit');

		return true;
	}
}
