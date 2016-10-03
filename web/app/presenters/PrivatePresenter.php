<?php

namespace App\Presenters;

use Nette;


class PrivatePresenter extends BasePresenter
{
    
    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->title = "Soukromí";
    }
}