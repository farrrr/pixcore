<?php

/**
 * Pix_Timeline 在有 updates, follow 兩個 table 的情況下，動態組出 timeline 功能
 *
 * @abstract
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw>
 */
class Pix_Timeline implements Iterator
{
    protected $_user;
    protected $_user_id;

    public function __construct($user, $user_id, $group = false)
    {
	$this->_user = $user;
	$this->_user_id = $user_id;
	$this->_group = $group;
    }

    /**
     * _updatetime 所有 defenders 中最新時間
     *
     * @var int
     * @access protected
     */
    protected $_updatetime = 0;

    /**
     * _user_updatetimes 所有 defeneders 中還未塞入 updates 的時間
     *
     * @var array
     * @access protected
     */
    protected $_user_updatetimes = array();

    /**
     * _updates 目前的 timeline
     *
     * @var array
     * @access protected
     */
    protected $_updates = array();

    /**
     * search_after 用 binary search 取得 $val 在 $array 第幾個位置
     *
     * @param array $array
     * @param string $val
     * @access protected
     * @return void
     */
    protected function search_after($array, $val)
    {
	$end = count($array) - 1;
	$start = 0;

	while ($end >= $start) {
	    $mid = floor(($start + $end) / 2);

	    if ($array[$mid] > $val) {
		$start = $mid + 1;
	    } elseif ($array[$mid] < $val) {
		$end = $mid - 1;
	    } else {
		return $mid + 1;
	    }
	}
	return $end + 1;
    }

    protected $_after = null;

    /**
     * after 指定要顯示哪個時間之後
     *
     * @param string $after
     * @access public
     * @return void
     */
    public function after($after)
    {
	$this->_after = $after;
	return $this;
    }

    public function current()
    {
	$update = $this->_user->getUpdateById($this->_current_update);
	if ($this->_group) {
	    if (!$g = $this->_user->getGroup($this->_current_update)) {
		return array($update, false);
	    }
	    $this->_grouped[$g] = true;
	    return array($update, true, $this->_user->getGroupMember($this->_user_id, $g));
	}
	return $update;
    }

    protected function _isGroupShowed($update)
    {
	if (!$this->_group) {
	    return false;
	}

	if (!$g = $this->_user->getGroup($update)) {
	    return false;
	}

	return $this->_grouped[$g];
    }

    public function next()
    {
	// 先把上一個動態的 id 記錄起來在 $prev_update ，並且來找下一個該被顯示的存進 _current_update
	if ($prev_update = $this->_current_update) {
	    $this->_showed[$this->_getID($prev_update)] = true;
	    $this->_current_update = null;
	}

	while (true) {
	    // 最多只找 20 秒
	    if (time() - $this->_scan_start > 20) {
                error_log("Pix_Timeline Warning: execution time of 20 seconds exceeded on {$this->_user_id}");
		break;
	    }

	    // _user_updatetimes 存放的是目前訂閱了哪些使用者他們的 update time list
	    // 如果 _user_updatetimes 不存在的話，就表示已經沒有更多的動態了，剩下的都在 _updates 內了
	    if (!$this->_user_updatetimes) {
		// 如果有前一個動態，就找他之後一個
		if ($prev_update) {
		    $pos = $this->search_after($this->_updates, $prev_update);
		    $current = $this->_updates[$pos];
		} else {
		    // 要不然就取出 _updates 的第一個
		    $current = current($this->_updates);
		}

		// 都找不到就表示結束了，已經沒有動態了
		if (!$current) {
		    break;
		}

		// 已經印過的動態或者是可以被群組化的有被印過的話就不再印了
		if ($this->_showed[$this->_getID($current)] or $this->_isGroupShowed($current)) {
		    $prev_update = $current;
		    continue;
		}

		// 找到一個新動態了，跳出來結束
		$this->_current_update = $current;
		break;
	    }

	    // 把最新的人取出來，如果最新的人還比 current 舊的話表示不用再往下抓了
	    foreach ($this->_user_updatetimes as $user_id => $not_used) {
		if ($this->_user_counts[$user_id] > 300) {
		    unset($this->_user_updatetimes[$user_id]);
		    continue;
		}
		break;
	    }

	    if ($prev_update) {
		$pos = $this->search_after($this->_updates, $prev_update);
		$current = $this->_updates[$pos];
	    } else {
		$current = current($this->_updates);
	    }

	    if ($current and $this->_user_updatetimes[$user_id] < $current) {
		$this->_current_update = $current;
		if ($this->_showed[$this->_getID($current)] or $this->_isGroupShowed($current)) {
		    $prev_update = $current;
		    continue;
		}
		break;
	    }

	    // 找出更新時間
	    if (!$time = $this->_user_updatetimes[$user_id]) {
		unset($this->_user_updatetimes[$user_id]);
		continue;
	    }

	    $updates = $this->_user->getUserUpdatesWithCache($user_id, $time);
	    $this->_user_counts[$user_id] += count($updates);

	    if ($oldest_time = array_pop($updates)) {
		array_push($updates, $oldest_time);
		$this->_user_updatetimes[$user_id] = $oldest_time;
	    } else {
		unset($this->_user_updatetimes[$user_id]);
	    }
	    $this->_updates = array_unique(array_merge($this->_updates, $updates));
	    rsort($this->_updates);
	    arsort($this->_user_updatetimes);
	}
    }

    public function key()
    {
	return $this->_current_update;
    }

    public function valid()
    {
	return $this->_current_update;
    }

    protected function _getID($id)
    {
	list($timestamp, $id) = explode('-', $id, 2);
	return $id;
    }

    public function rewind()
    {
	$this->_showed = array();
	$this->_grouped = array();
	$this->_scan_start = time();

	$this->_updates = array();
	$this->_user_updatetimes = $this->_user->getFollowings($this->_user_id);

	if ($this->_user_updatetimes) {
	    $this->_max_updatetime = max($this->_user_updatetimes);
	} else {
	    $this->_max_updatetime = null;
	}

	$this->_current_update = is_null($this->_after) ? null : $this->_after;

	// 如果有用分群組模式並且還有指定 after 的話，就必需要把前面所有動態都先跑過一次 orz
	if ($this->_group and !is_null($this->_after)) {
	    $this->_current_update = null;
	    do {
		$this->next();
		if ($this->_current_update >= $this->_after) {
		    if ($g = $this->_user->getGroup($this->_current_update)) {
			$this->_grouped[$g] = true;
		    }
		    continue;
		}
		break;
	    } while(true);
	}
	$this->next();
    }
}
