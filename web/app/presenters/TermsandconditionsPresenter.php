<?php

namespace App\Presenters;

use Nette;


class TermsandconditionsPresenter extends BasePresenter
{
    
    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->title = "Podmínky";
    }
}