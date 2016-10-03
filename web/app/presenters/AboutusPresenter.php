<?php

namespace App\Presenters;

use Nette;


class AboutusPresenter extends BasePresenter
{
    
    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->title = "O nás";
    }
}