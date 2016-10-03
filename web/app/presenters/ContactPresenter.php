<?php

namespace App\Presenters;

use Nette;


class ContactPresenter extends BasePresenter
{
    
    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->title = "Kontakt";
    }
}