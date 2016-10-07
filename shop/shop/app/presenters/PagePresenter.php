<?php

namespace App\Presenters;

use Nette;


class PagePresenter extends BasePresenter
{
    public function beforeRender()
    {
         parent::beforeRender();
         $this->template->title = $this->translator->translate("ui.menuItems.page");
    }
}