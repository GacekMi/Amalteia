<?php

namespace App\Presenters;

use Nette;


class HomepagePresenter extends BasePresenter
{
    
    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->title = "Amalteia";
    }
}
