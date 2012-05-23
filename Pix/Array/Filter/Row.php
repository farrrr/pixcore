<?php

class Pix_Array_Filter_Row implements Pix_Array_Filter
{
    public function filter($row, $options)
    {
        $method = array_shift($options);
        return call_user_func_array(array($row, $method), $options);
    }
}
