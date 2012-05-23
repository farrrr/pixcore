<?php

class User extends Pix_Table
{
    public $_name = 'user';

    public function getLink($type)
    {
	$link = new mysqli;
	$link->connect('server', 'port');
	return $link;
    }

    public function __construct()
    {
	$this->_primary = 'user_id';

	$this->_columns['user_id'] = array('type' => 'int', 'size' => '10', 'unsigned' => true);
	$this->_columns['email'] = array('type' => 'text');
	$this->_columns['created_at'] = array('type' => 'int', 'size' => '10', 'unsigned' => true);
	$this->_columns['created_from'] = array('type' => 'int', 'size' => '10', 'unsigned' => true);

    }

}

