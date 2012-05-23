<?php

/**
 * Pix_Timeline_User Timeline 內的 User 的角色
 *
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw>
 */
abstract class Pix_Timeline_User
{
    protected $_cache_prefix = 'Pix_Timeline_User:';
    protected $_cache;

    public function __construct($options = array())
    {
	if (isset($options['cache_prefix'])) {
	    $this->_cache_prefix = $options['cache_prefix'];
	}

	if (isset($options['cache'])) {
	    $this->_cache = $options['cache'];
	} else {
	    $this->_cache = new Pix_Cache;
	}
    }

    /**
     * getUserUpdates 需要實作這個，取得現在使用者在 $before 之前的動態
     *
     * @param mixed $before
     * @abstract
     * @access public
     * @return void
     */
    abstract public function getUserUpdates($user_id, $before = null);

    public function getCache()
    {
	return $this->_cache;
    }

    public function getUserUpdatesWithCache($user_id, $before = null)
    {
	$cache = $this->getCache();
	$cache_key = "Pix_Timeline_Updates_Cache1:{$user_id}:{$updatetime}:{$before}";
	if (($data = $cache->load($cache_key))) {
	    return explode(',', $data);
	}

	$data = $this->getUserUpdates($user_id, $before);
	$cache->save($cache_key, implode(',', $data));
	return $data;
    }

    /**
     * getFollowings 取得訂閱列表
     *
     * @abstract
     * @access public
     * @return void
     */
    abstract public function getFollowings($user_id, $after = 0);

    /**
     * getTimeline 取得 Pix_TimeLine object
     *
     * @param string $user_id 給 getFollowings() 吃的列表 ID
     * @param boolean $group 是否要把同 group 動態合在一起顯示
     * @access public
     * @return Pix_Timeline
     */
    public function getTimeline($user_id, $group = false)
    {
	return new Pix_Timeline($this, $user_id, $group);
    }
}
