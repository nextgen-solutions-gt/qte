<?php
/**
 *
 * Quick Title Edition extension for the phpBB Forum Software package
 *
 * @copyright (c) 2023, Kailey Snay, https://www.snayhomelab.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kaileymsnay\qte;

/**
 * Quick Title Edition service info
 */
class qte
{
	const KEEP = -2;
	const DELETE = -1;

	/** @var \phpbb\auth\auth */
	protected $auth;

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
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/** @var string */
	protected $table_prefix;

	/** @var array */
	private $_attr;

	/** @var string */
	private $_name = [];

	/**
	 * Constructor
	 *
	 * @param \phpbb\auth\auth                      $auth
	 * @param \phpbb\cache\driver\driver_interface  $cache
	 * @param \phpbb\db\driver\driver_interface     $db
	 * @param \phpbb\language\language              $language
	 * @param \phpbb\log\log                        $log
	 * @param \phpbb\request\request                $request
	 * @param \phpbb\template\template              $template
	 * @param \phpbb\user                           $user
	 * @param string                                $root_path
	 * @param string                                $php_ext
	 * @param string                                $table_prefix
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\cache\driver\driver_interface $cache, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $root_path, $php_ext, $table_prefix)
	{
		$this->auth = $auth;
		$this->cache = $cache;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->table_prefix = $table_prefix;

		$this->_get_attributes();
	}

	/**
	 * Get topic attributes username
	 */
	public function get_users_by_topic_id($topic_list)
	{
		if (!empty($topic_list))
		{
			$sql = 'SELECT u.user_id, u.username, u.user_colour
				FROM ' . $this->table_prefix . 'users u
				LEFT JOIN ' . $this->table_prefix . 'topics t ON (u.user_id = t.topic_attr_user)
				WHERE ' . $this->db->sql_in_set('t.topic_id', array_map('intval', $topic_list)) . '
					AND t.topic_attr_user <> ' . ANONYMOUS;
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->_name[$row['user_id']] = [
					'user_id'		=> (int) $row['user_id'],
					'username'		=> $row['username'],
					'user_colour'	=> $row['user_colour'],
				];
			}
			$this->db->sql_freeresult();
		}
	}

	/**
	 * Get attribute name
	 */
	public function get_attr_name_by_id($attr_id)
	{
		return $this->_attr[$attr_id]['attr_name'];
	}

	/**
	 * Get attribute author
	 */
	public function get_users_by_user_id($user_id)
	{
		if (!isset($this->_name[$user_id]))
		{
			$sql = 'SELECT user_id, username, user_colour
				FROM ' . $this->table_prefix . 'users
				WHERE user_id = ' . (int) $user_id;
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->_name[$row['user_id']] = [
					'user_id'		=> (int) $row['user_id'],
					'username'		=> $row['username'],
					'user_colour'	=> $row['user_colour'],
				];
			}
			$this->db->sql_freeresult();
		}
	}

	/**
	 * Generate a list of attributes based on permissions
	 */
	public function attr_select($forum_id, $author_id = 0, $attribute_id = 0, $viewtopic_url = '', $mode = '')
	{
		$show_select = false;
		$current_time = time();

		$s_edit = (bool) $this->auth->acl_get('m_qte_attr_edit', $forum_id);
		$s_delete = (bool) $this->auth->acl_get('m_qte_attr_delete', $forum_id);
		$s_author = (bool) ($this->user->data['is_registered'] && $this->user->data['user_id'] == $author_id);

		if ($s_edit || $s_author || $mode == 'post')
		{
			foreach ($this->_attr as $attr)
			{
				if (!$this->auth->acl_get('f_qte_attr_' . $attr['attr_id'], $forum_id))
				{
					continue;
				}

				// Show the selector
				$show_select = true;

				// Parse the attribute name
				$attribute_name = str_replace(['%mod%', '%date%'], [$this->user->data['username'], $this->user->format_date($current_time, $attr['attr_date'])], $this->language->lang($attr['attr_name']));

				$this->template->assign_block_vars('attributes', [
					'QTE_ID'		=> $attr['attr_id'],
					'QTE_NAME'		=> $attribute_name,
					'QTE_DESC'		=> $this->language->lang($attr['attr_desc']),
					'QTE_COLOUR'	=> $this->attr_colour($attr['attr_name'], $attr['attr_colour']),

					'S_SELECTED'	=> (!empty($attribute_id) && ($attr['attr_id'] == $attribute_id)) ? true : false,
					'S_QTE_DESC'	=> !empty($attr['attr_desc']) ? true : false,

					'U_QTE_URL'	=> !empty($viewtopic_url) ? append_sid($viewtopic_url, ['attr_id' => $attr['attr_id']]) : false,
				]);
			}
		}

		$this->template->assign_vars([
			'S_QTE_SELECT'		=> ($show_select || $s_delete && ($attribute_id || !$author_id)) ? true : false,
			'S_QTE_DELETE'		=> $s_author || $s_delete,
			'S_QTE_EMPTY'		=> (empty($attribute_id)) ? true : false,
			'S_QTE_SELECTED'	=> ($s_delete && ($attribute_id == self::DELETE)) ? true : false,
			'S_QTE_KEEP'		=> !empty($attribute_id) && ($attribute_id == self::KEEP) ? true : false,

			'U_QTE_URL'	=> !empty($viewtopic_url) ? append_sid($viewtopic_url, ['attr_id' => self::DELETE]) : false,
		]);
	}

	/**
	 * Generate a list of all attributes for search page
	 */
	public function attr_search()
	{
		foreach ($this->_attr as $attr)
		{
			// Parse the attribute name
			$attribute_name = str_replace(['%mod%', '%date%'], [$this->language->lang('QTE_KEY_USERNAME'), $this->language->lang('QTE_KEY_DATE')], $this->language->lang($attr['attr_name']));

			$this->template->assign_block_vars('attributes', [
				'QTE_ID'		=> $attr['attr_id'],
				'QTE_NAME'		=> $attribute_name,
				'QTE_DESC'		=> $this->language->lang($attr['attr_desc']),
				'QTE_COLOUR'	=> $this->attr_colour($attr['attr_name'], $attr['attr_colour']),

				'S_QTE_DESC'	=> !empty($attr['attr_desc']) ? true : false,
			]);
		}
	}

	/**
	 * Generate a list of attributes for viewforum page
	 */
	public function attr_sort($forum_id = 0, $attribute_id = 0)
	{
		foreach ($this->_attr as $attr)
		{
			$forum_allowed = $this->auth->acl_getf('f_qte_attr_' . $attr['attr_id'], true);

			if (isset($forum_allowed[$forum_id]))
			{
				// Parse the attribute name
				$attribute_name = str_replace(['%mod%', '%date%'], [$this->language->lang('QTE_KEY_USERNAME'), $this->language->lang('QTE_KEY_DATE')], $this->language->lang($attr['attr_name']));

				$this->template->assign_block_vars('attributes', [
					'QTE_ID'		=> $attr['attr_id'],
					'QTE_NAME'		=> $attribute_name,
					'QTE_DESC'		=> $this->language->lang($attr['attr_desc']),
					'QTE_COLOUR'	=> $this->attr_colour($attr['attr_name'], $attr['attr_colour']),

					'S_SELECTED'	=> (!empty($attribute_id) && ($attr['attr_id'] == $attribute_id)) ? true : false,
					'S_QTE_DESC'	=> !empty($attr['attr_desc']) ? true : false,
				]);
			}
		}
	}

	/**
	 * Generate a default attribute list for a forum
	 */
	public function attr_default($forum_id = 0, $attribute_id = 0)
	{
		foreach ($this->_attr as $attr)
		{
			$forum_allowed = $this->auth->acl_getf('f_qte_attr_' . $attr['attr_id'], true);

			if (isset($forum_allowed[$forum_id]))
			{
				// Parse the attribute name
				$attribute_name = str_replace(['%mod%', '%date%'], [$this->language->lang('QTE_KEY_USERNAME'), $this->language->lang('QTE_KEY_DATE')], $this->language->lang($attr['attr_name']));

				$this->template->assign_block_vars('attributes', [
					'QTE_ID'		=> $attr['attr_id'],
					'QTE_NAME'		=> $attribute_name,
					'QTE_DESC'		=> $this->language->lang($attr['attr_desc']),
					'QTE_COLOUR'	=> $this->attr_colour($attr['attr_name'], $attr['attr_colour']),

					'S_SELECTED'	=> (!empty($attribute_id) && ($attr['attr_id'] == $attribute_id)) ? true : false,
					'S_QTE_DESC'	=> !empty($attr['attr_desc']) ? true : false,
				]);
			}
		}
	}

	/**
	 * Generate attribute for topic title
	 */
	public function attr_display($attribute_id = 0, $user_id = 0, $timestamp = 0)
	{
		if (empty($attribute_id) || empty($user_id) || empty($timestamp))
		{
			return false;
		}

		if (isset($this->_attr[$attribute_id]))
		{
			$attribute_colour = $this->attr_colour($this->_attr[$attribute_id]['attr_name'], $this->_attr[$attribute_id]['attr_colour']);

			if (isset($this->_name[$user_id]['user_id']))
			{
				$attribute_username = get_username_string(($this->_attr[$attribute_id]['attr_user_colour'] ? 'no_profile' : 'username'), $this->_name[$user_id]['user_id'], $this->_name[$user_id]['username'], $this->_name[$user_id]['user_colour']);
			}
			else
			{
				$attribute_username = $this->language->lang('GUEST');
			}

			$attribute_date = $this->user->format_date($timestamp, $this->_attr[$attribute_id]['attr_date']);
			$attribute_name = str_replace(['%mod%', '%date%'], [$attribute_username, $attribute_date], $this->language->lang($this->_attr[$attribute_id]['attr_name']));

			return !$this->_attr[$attribute_id]['attr_type'] ? '<span' . $attribute_colour . '>' . $attribute_name . '</span>' : $this->attr_img_key($this->_attr[$attribute_id]['attr_img'], $attribute_name);
		}
	}

	/**
	 * Generate attribute for page title
	 */
	public function attr_title($attribute_id = 0, $user_id = 0, $timestamp = 0)
	{
		if (empty($attribute_id) || empty($user_id) || empty($timestamp))
		{
			return false;
		}

		if (isset($this->_attr[$attribute_id]))
		{
			if (isset($this->_name[$user_id]['user_id']))
			{
				$attribute_username = get_username_string('username', $this->_name[$user_id]['user_id'], $this->_name[$user_id]['username'], $this->_name[$user_id]['user_colour']);
			}
			else
			{
				$attribute_username = $this->language->lang('GUEST');
			}

			$attribute_date = $this->user->format_date($timestamp, $this->_attr[$attribute_id]['attr_date']);

			return str_replace(['%mod%', '%date%'], [$attribute_username, $attribute_date], $this->language->lang($this->_attr[$attribute_id]['attr_name']));
		}
	}

	/**
	 * Change topic attribute
	 */
	public function attr_apply($attribute_id = 0, $topic_id = 0, $forum_id = 0, $topic_attribute = 0, $author_id = 0, $viewtopic_url = '')
	{
		if (empty($topic_id) || empty($forum_id) || empty($attribute_id))
		{
			return;
		}

		$s_edit = $this->auth->acl_get('m_qte_attr_edit', $forum_id) || $this->auth->acl_get('f_qte_attr_' . $attribute_id, $forum_id) && $this->user->data['is_registered'] && $this->user->data['user_id'] == $author_id;
		$s_delete = $this->auth->acl_get('m_qte_attr_delete', $forum_id);

		if (!$s_edit && $attribute_id != self::DELETE || !$s_delete && $attribute_id == self::DELETE)
		{
			trigger_error($this->language->lang('NOT_AUTHORISED'));
		}

		// Default values
		$fields = [
			'topic_attr_id'		=> 0,
			'topic_attr_user'	=> 0,
			'topic_attr_time'	=> 0,
		];

		$current_time = time();

		if ($attribute_id != self::DELETE)
		{
			$fields = [
				'topic_attr_id'		=> $attribute_id,
				'topic_attr_user'	=> $this->user->data['user_id'],
				'topic_attr_time'	=> $current_time,
			];
		}

		$sql = 'UPDATE ' . $this->table_prefix . 'topics
			SET ' . $this->db->sql_build_array('UPDATE', $fields) . '
			WHERE topic_id = ' . (int) $topic_id;
		$this->db->sql_query($sql);

		$sql = 'SELECT topic_id
			FROM ' . $this->table_prefix . 'topics
			WHERE topic_moved_id = ' . (int) $topic_id;
		$result = $this->db->sql_query($sql);
		$shadow_topic_id = (int) $this->db->sql_fetchfield('topic_id');
		$this->db->sql_freeresult($result);

		if (!empty($shadow_topic_id))
		{
			$sql = 'UPDATE ' . $this->table_prefix . 'topics
				SET ' . $this->db->sql_build_array('UPDATE', $fields) . '
				WHERE topic_id = ' . (int) $shadow_topic_id;
			$this->db->sql_query($sql);
		}

		meta_refresh(3, $viewtopic_url);

		$message = $this->language->lang('QTE_ATTRIBUTE_' . ($attribute_id == -1 ? 'DELETED' : (empty($topic_attribute) ? 'ADDED' : 'UPDATED')));

		if ($this->request->is_ajax())
		{
			$json_response = new \phpbb\json_response;
			$json_response->send([
				'success'	=> true,

				'MESSAGE_TITLE'	=> $this->language->lang('INFORMATION'),
				'MESSAGE_TEXT'	=> $message,
				'NEW_ATTRIBUTE'	=> $this->attr_display($attribute_id, $this->user->data['user_id'], $current_time),
			]);
		}

		$message .= '<br /><br />' . $this->language->lang('RETURN_PAGE', '<a href="' . $viewtopic_url . '">', '</a>');

		trigger_error($message);
	}

	/**
	 * Change topic attribute in mcp
	 */
	public function mcp_attr_apply($attribute_id = 0, $forum_id = 0, $topic_ids = [])
	{
		$s_edit = $this->auth->acl_get('m_qte_attr_edit', $forum_id);
		$s_delete = $this->auth->acl_get('m_qte_attr_delete', $forum_id);

		if (!$s_edit && $attribute_id != self::DELETE || !$s_delete && $attribute_id == self::DELETE)
		{
			return;
		}

		if (!count($topic_ids))
		{
			trigger_error($this->language->lang('NO_TOPIC_SELECTED'));
		}

		if (!phpbb_check_ids($topic_ids, $this->table_prefix . 'topics', 'topic_id'))
		{
			return;
		}

		// Default values
		$fields = [
			'topic_attr_id'		=> 0,
			'topic_attr_user'	=> 0,
			'topic_attr_time'	=> 0,
		];

		$current_time = time();

		if ($attribute_id != self::DELETE)
		{
			$fields = [
				'topic_attr_id'		=> $attribute_id,
				'topic_attr_user'	=> $this->user->data['user_id'],
				'topic_attr_time'	=> $current_time,
			];
		}

		$sql = 'SELECT topic_id, forum_id, topic_title, topic_attr_id
			FROM ' . $this->table_prefix . 'topics
			WHERE ' . $this->db->sql_in_set('topic_id', array_map('intval', $topic_ids));
		$result = $this->db->sql_query($sql);

		// Log this action
		while ($row = $this->db->sql_fetchrow($result))
		{
			$message = ($attribute_id == -1) ? 'DELETED' : (empty($row['topic_attr_id']) ? 'ADDED' : 'UPDATED');

			$additional_data = [
				'forum_id'	=> $row['forum_id'],
				'topic_id'	=> $row['topic_id'],
				$row['topic_title'],
			];

			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, $this->language->lang('MCP_ATTRIBUTE_' . $message), $current_time, $additional_data);
		}
		$this->db->sql_freeresult($result);

		$sql = 'UPDATE ' . $this->table_prefix . 'topics
			SET ' . $this->db->sql_build_array('UPDATE', $fields) . '
			WHERE ' . $this->db->sql_in_set('topic_id', array_map('intval', $topic_ids));
		$this->db->sql_query($sql);

		$sql = 'SELECT topic_id
			FROM ' . $this->table_prefix . 'topics
			WHERE ' . $this->db->sql_in_set('topic_moved_id', array_map('intval', $topic_ids));
		$result = $this->db->sql_query($sql);
		$shadow_topic_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$shadow_topic_ids[] = (int) $row['topic_id'];
		}
		$this->db->sql_freeresult($result);

		if (count($shadow_topic_ids))
		{
			$sql = 'UPDATE ' . $this->table_prefix . 'topics
				SET ' . $this->db->sql_build_array('UPDATE', $fields) . '
				WHERE ' . $this->db->sql_in_set('topic_id', array_map('intval', $shadow_topic_ids));
			$this->db->sql_query($sql);
		}

		$redirect = append_sid($this->root_path . 'mcp.' . $this->php_ext, "f=$forum_id&amp;i=main&amp;mode=forum_view", true, $this->user->session_id);

		meta_refresh(3, $redirect);

		trigger_error($this->language->lang('QTE_TOPIC' . (count($topic_ids) == 1 ? '' : 'S') . '_ATTRIBUTE_' . ($message ?? 'ADDED')) . '<br /><br />' . $this->language->lang('RETURN_PAGE', '<a href="' . $redirect . '">', '</a>'));
	}

	/**
	 * Get function
	 */
	public function get_attr()
	{
		return $this->_attr;
	}

	/**
	 * Check if an image key exists
	 */
	public function attr_img_key($key, $alt)
	{
		return empty($key) ? '' : (preg_match('#^[a-z0-9_-]+$#i', $key) ? $this->user->img($key, $alt) : '<img src="' . (preg_match('#^(ht|f)tp[s]?\://#i', $key) ? $key : $this->root_path . $key) . '" alt="' . $alt . '" title="' . $alt . '" />');
	}

	/**
	 * Build class and style attribute
	 */
	public function attr_colour($a_name, $a_colour)
	{
		if ($a_name != $this->language->lang($a_name))
		{
			$a_class_name = preg_replace('#[^a-z0-9 _-]#', '', strtolower($a_name));
		}

		return ' class="qte-attr ' . ($a_class_name ?? '') . '"' . (!empty($a_colour) ? ' style="color: #' . $a_colour . '; font-weight: bold;"' : '');
	}

	/**
	 * Get attributes from database
	 */
	private function _get_attributes()
	{
		if (($this->_attr = $this->cache->get('_attr')) === false)
		{
			$sql = 'SELECT *
				FROM ' . $this->table_prefix . 'topics_attr
				ORDER BY left_id ASC';
			$result = $this->db->sql_query($sql);
			$this->_attr = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->_attr[$row['attr_id']] = [
					'attr_id'			=> (int) $row['attr_id'],
					'attr_type'			=> (bool) $row['attr_type'],
					'attr_name'			=> $row['attr_name'],
					'attr_desc'			=> $row['attr_desc'],
					'attr_img'			=> $row['attr_img'],
					'attr_colour'		=> $row['attr_colour'],
					'attr_date'			=> $row['attr_date'],
					'attr_user_colour'	=> (bool) $row['attr_user_colour'],
				];
			}
			$this->db->sql_freeresult();

			$this->cache->put('_attr', $this->_attr);
		}
	}
}
