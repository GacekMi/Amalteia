<?php

namespace App\Presenters;

use Nette;


class FaqPresenter extends BasePresenter
{
    
    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->title = "FAQ";
    }
}