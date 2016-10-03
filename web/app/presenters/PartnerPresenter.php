<?php

namespace App\Presenters;

use Nette;


class PartnerPresenter extends BasePresenter
{
    
    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->title = "Partner";
    }
}