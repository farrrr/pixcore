<?php

class ErrorController extends Pix_Controller 
{
    public function errorAction()
    {
	echo strval($this->view->exception);
    }
}
