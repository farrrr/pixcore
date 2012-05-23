<?php

class Pix_Table_Db_Adapter_PDO_Result
{
    public function __construct($res)
    {
        $this->_res = $res;
    }

    public function fetch_assoc()
    {
        $ret = $this->_res->fetch(PDO::FETCH_ASSOC);
        return $ret;
    }

    public function fetch_array()
    {
        $ret = $this->_res->fetch(PDO::FETCH_NUM);
        return $ret;
    }

    public function free_result()
    {
    }
}
