<?php

/*

CREATE TABLE IF NOT EXISTS `task` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `queueid` int(10) unsigned NOT NULL,
  `group` varchar(255) character set latin1 collate latin1_general_ci NOT NULL,
  `priority` tinyint(4) NOT NULL default '0',
  `identify` varchar(255) character set latin1 collate latin1_general_ci default NULL
  `created_at` int(10) unsigned NOT NULL,
  `lastupdated_at` int(10) unsigned NOT NULL,
  `status` varchar(255) character set latin1 collate latin1_general_ci NOT NULL,
  `content` text character set latin1 collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 */

/**
 * Pix_Queue2_Task
 *
 * @package Pix_Queue2
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Queue2_Task
{
    public $content;
    public $group;
    public $id = null;
    public $identify;
    public $priority;
    public $queue = null;
    public $scheduled_at = 0;
    public $created_at;

    public function __construct($content, $opts = array())
    {
	// 如果是 object 的話，用 object 的值當作 task 的資料
	if (is_object($content)) {
	    $this->content = $content->content;
	    $this->group = $content->group;
	    $this->id = $content->id;
	    $this->identify = $content->identify;
	    $this->priority = $content->priority;
	    $this->scheduled_at = intval($content->scheduled_at);
	    $this->created_at = $content->created_at;
	} else {
	    $this->content = $content;
	    $this->group = $this->decideGroup($opts);
	    $this->identify = $this->decideIdentify($opts);
	    $this->priority = $this->decidePriority($opts);
	    $this->scheduled_at = intval($opts['scheduled_at']);
	}
    }

    public function abort()
    {
	$queue = $this->queue;

	$db = $queue->db;

	$id = $this->id;
	$now = time();
	$qId = $queue->queueId;
	$qTbl = $queue->queueTable;

	$db->query(sprintf('UPDATE `%s` SET `status` = "", `lastupdated_at` = ? WHERE `id` = ? LIMIT 1', $qTbl), $now, $id);
    }

    public function add($opts = array())
    {
	$queue = $this->queue;

	$db = $queue->db;

	$now = time();
	$qId = $queue->queueId;
	$qTbl = $queue->queueTable;

	$ret = $db->query(sprintf('INSERT INTO `%s` (`queueid`, `group`, `priority`, `identify`, `scheduled_at`, `created_at`, `lastupdated_at`, `status`, `content`) VALUE (?, ?, ?, ?, ?, ?, ?, "", ?)', $qTbl), $qId, $this->group, $this->priority, $this->identify, $this->scheduled_at, $now, $now, $this->content);
	if ($ret > 0) {
	    $this->id = $db->lastInsertId();
	}
    }

    protected function decideGroup($opts)
    {
	if (array_key_exists('group', $opts)) {
	    return $opts['group'];
	} else {
	    return '';
	}
    }

    protected function decideIdentify($opts)
    {
	if (array_key_exists('identify', $opts)) {
	    return $opts['identify'];
	} else {
	    return null;
	}
    }

    protected function decidePriority($opts)
    {
	if (array_key_exists('priority', $opts)) {
	    $num = intval($opts['priority']);
	    if ($num > 127) {
		$num = 127;
	    } elseif ($num < -128) {
		$num = -128;
	    }
	} else {
	    $num = 0;
	}

	return $num;
    }

    public function done()
    {
	$queue = $this->queue;

	$db = $queue->db;

	$id = $this->id;
	$qId = $queue->queueId;
	$qTbl = $queue->queueTable;

	$db->query(sprintf('DELETE FROM `%s` WHERE `id` = ? LIMIT 1', $qTbl), $id);
    }

    public function status()
    {
	$queue = $this->queue;

	$db = $queue->db;

	// FIXME
    }
}

