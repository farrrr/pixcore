<?php

/*

CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) character set latin1 collate latin1_general_ci NOT NULL,
  `timeout` int(10) unsigned NOT NULL default '3600',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 */

require_once(__DIR__ . '/Db.php');

/**
 * Pix_Queue2
 *
 * @package Pix_Queue2
 * @copyright 2003-2010 PIXNET
 * @author Gea-Suan Lin <gslin@pixnet.tw>
 */
class Pix_Queue2
{
    public $db;
    public $timeout;
    public $queueId = null;
    public $queueName;
    public $queueTable = '';

    public function __construct($name, $db, $opts = array())
    {
	$this->queueName = $name;

	if (is_string($db)) {
	    $this->db = new Pix_Db($db);
	} else {
	    $this->db = $db;
        }

	$this->initDb();
    }

    protected function _decideGroup($opts)
    {
        if (array_key_exists('group', $opts)) {
            return $opts['group'];
        } else {
            return '';
        }
    }

    protected function _decideMax($opts)
    {
        if (array_key_exists('max', $opts)) {
            $maxNum = intval($opts['max']);
            if ($maxNum < 0) {
                $maxNum = 1;
            }
        } else {
            $maxNum = null;
        }

        return $maxNum;
    }

    protected function _decideScheduledBefore($opts)
    {
        if (array_key_exists('scheduled_before', $opts)) {
            $before = intval($opts['scheduled_before']);
            if ($before < 0) {
                $before = null;
            }
        } else {
            $before = null;
        }

        return $before;
    }

    protected function _decideWait($opts)
    {
        if (array_key_exists('wait', $opts)) {
            $waitTime = intval($opts['wait']);
            if ($waitTime < 0) {
                $waitTime = null;
            }
        } else {
            $waitTime = null;
        }

        return $waitTime;
    }

    public function cleanTasks()
    {
	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;
	$timeout = $this->timeout;

	// 把過期的 task 清空 flag
	if ($timeout > 0) {
	    $now = time();

	    // 先看看有沒有過期的工作，沒有的話就不需要 UPDATE，可以減少 binlog
	    $r = $db->fetchOne(sprintf('SELECT COUNT(*) FROM `%s` WHERE `lastupdated_at` < ? AND `status` != ""', $qTbl), $now - $timeout);

	    // 如果有過期的工作就歸 0
	    if ($r > 0) {
		$now = time();
		$db->query(sprintf('UPDATE `%s` SET `status` = "", `lastupdated_at` = ? WHERE `lastupdated_at` < ? AND `status` != ""', $qTbl), $now, $now - $timeout);
            }
        }
    }

    public function countTasks($opts = array())
    {
	$db = $this->db;
        $group = $this->_decideGroup($opts);
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	if ('' == $group) {
	    $num = $db->fetchOne(sprintf('SELECT COUNT(*) FROM `%s` WHERE `scheduled_at` < ?', $qTbl), time());
	} else {
	    $num = $db->fetchOne(sprintf('SELECT COUNT(*) FROM `%s` WHERE `group` = ? AND `scheduled_at` < ?', $qTbl), $group, time());
        }

	return intval($num);
    }

    public function createTask($content, $opts = array())
    {
	$task = new Pix_Queue2_Task($content, $opts);
	$task->queue = $this;

	return $task;
    }

    public function findTask($id)
    {
	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	$r = $db->fetchRow(sprintf('SELECT * FROM `%s` WHERE `id` = ? LIMIT 1', $qTbl), $id);
	if ($r) {
	    return $this->createTask($r);
        }

	return null;
    }

    public function findTaskByIdentify($identify)
    {
	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	$r = $db->fetchRow(sprintf('SELECT * FROM `%s` WHERE `identify` = ?', $qTbl), $identify);
	if ($r) {
	    return $this->createTask($r);
        }

	return null;
    }

    public function getAllGroups()
    {
	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	$rs = $db->fetchAll(sprintf('SELECT `group`, COUNT(`group`) AS `num` FROM `%s` WHERE `group` != "" GROUP BY `group` ORDER BY `num` DESC', $qTbl));

	$ret = array();
	foreach ($rs as $r) {
	    $ret[$r->group] = $r->num;
        }

	return $ret;
    }

    public function getLargestGroup()
    {
	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	$r = $db->fetchRow(sprintf('SELECT `group`, COUNT(`group`) AS `num` FROM `%s` WHERE `group` != "" AND `status` = "" AND `scheduled_at` < ? GROUP BY `group` ORDER BY `num` DESC LIMIT 0, 1', $qTbl), time());

	// 如果 $r 沒有東西，剛好是傳回 null
	return $r->group;
    }

    public function getTaskCount()
    {
	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	$num = intval($db->fetchOne(sprintf('SELECT COUNT(*) FROM `%s` WHERE `scheduled_at` < ?', $qTbl), time()));

	return $num;
    }

    public function getTasks($opts = array())
    {
	$startTime = time();

        $group = $this->_decideGroup($opts);
        $maxNum = $this->_decideMax($opts);
        $waitTime = $this->_decideWait($opts);
        $before = $scheduledBefore = $this->_decideScheduledBefore($opts);

	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	// 先清除過期工作
	$this->cleanTasks();

	for (;;) {
            if (is_null($scheduledBefore)) {
		$before = time();
            }

            // 先看看有沒有未做的工作，沒有的話就不需要 UPDATE，可以減少 binlog
            if ('' == $group) {
                $qStr = 'SELECT COUNT(*) FROM `%s` WHERE `status` = "" AND `scheduled_at` < ?';
                $num = $db->fetchOne(sprintf($qStr, $qTbl), $before);
            } else {
                $qStr = 'SELECT COUNT(*) FROM `%s` WHERE `status` = "" AND `group` = ? AND `scheduled_at` < ?';
                $num = $db->fetchOne(sprintf($qStr, $qTbl), $group, $before);
            }

	    $ret = array();

	    // 沒有未做工作的話就傳回空陣列
	    do {
		if (intval($num) < 0) {
		    break;
                }

		// 產生 unique status
		$u = implode('-', array(getmypid(), uniqid()));

                // 先決定要撈多少東西的條件
                $limitCond = is_null($maxNum) ? '' : "LIMIT $maxNum";
                $now = time();

		// 把 task 狀態改成 unique status
		try {
                    if ('' == $group) {
                        $qStr = "UPDATE `%s` SET `status` = ?, `lastupdated_at` = ? WHERE `status` = '' AND `scheduled_at` < ? ORDER BY `status` DESC, `scheduled_at` DESC, `priority` DESC, `id` DESC $limitCond";
                        $db->query(sprintf($qStr, $qTbl), $u, $now, $before);
                    } else {
                        $qStr = "UPDATE `%s` SET `status` = ?, `lastupdated_at` = ? WHERE `status` = '' AND `group` = ? AND `scheduled_at` < ? ORDER BY `group` DESC, `status` DESC, `scheduled_at` DESC, `priority` DESC, `id` DESC $limitCond";
                        $db->query(sprintf($qStr, $qTbl), $u, $now, $group, $before);
                    }
		} catch (Pix_Db_Exception $ex) {
                    error_log("Pix_Queue2 Warning:  Database encounter innodb deadlock");
                    // deadlock 了, 再 try 一次
		    break;
                }

		// 取出有拿到的 task 放入 $ret
		$rs = $db->fetchAll(sprintf('SELECT * FROM `%s` WHERE `status` = ?', $qTbl), $u);
		foreach ($rs as $r) {
		    $ret[] = $this->createTask($r);
                }
            } while (0);

	    // 有資料就跳出去
	    if (count($ret) > 0) {
		break;
            }

	    // 如果 $waitTime 有指定，且時間到的話就跳出去
	    if (!is_null($waitTime) and time() - $startTime >= $waitTime) {
		break;
            }

	    // 剩下的情況就 sleep(1) 再繼續 loop
	    sleep(1);
        }

	return $ret;
    }

    protected function initDb()
    {
	// 如果 queueId 已經存在表示已經連上 database
	if (!is_null($this->queueId)) {
	    return $this;
        }

	$db = $this->db;
        $qStr = 'SELECT `id` FROM `queue` WHERE `name` = ?';

        // 抓看看有沒有這個 queueName
        $this->queueId = $db->fetchOne($qStr, $this->queueName);

	// 如果沒有的話就 INSERT (但有可能別 process 的也 INSERT 而失敗，所以要加上 IGNORE)
	if (!$this->queueId) {
	    $db->query('INSERT IGNORE INTO `queue` SET `name` = ?', $this->queueName);

            // 不管 INSERT 成功或失敗，entry 應該都在了，抓出來放到 queueId
            $this->queueId = $db->fetchOne($qStr, $this->queueName);
        }
	$this->queueTable = sprintf('task_%d', $this->queueId);

	// 建立這個 Queue 用的 task Table
	$db->query(sprintf('CREATE TABLE IF NOT EXISTS `%s` LIKE task_tmpl', $this->queueTable));

	// 抓 timeout 值
	$this->timeout = $db->fetchOne('SELECT `timeout` FROM `queue` WHERE `id` = ?', $this->queueId);
    }

    public function purgeTasks()
    {
	$db = $this->db;
	$qId = $this->queueId;
	$qTbl = $this->queueTable;

	$db->query(sprintf('TRUNCATE TABLE `%s`', $qTbl));
    }
}

