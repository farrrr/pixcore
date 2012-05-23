<?php

abstract class Pix_Table_Db_Adapter_Abstract implements Pix_Table_Db_Adapter
{
    public function support($id)
    {
        return in_array($id, $this->getSupportFeatures());
    }

    public function getSupportFeatures()
    {
        return array();
    }
}
