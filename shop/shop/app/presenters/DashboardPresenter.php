<?php

namespace App\Presenters;

use Nette;


class DashboardPresenter extends PrivatePresenter
{
    public function beforeRender()
    {
         parent::beforeRender();
         $this->template->title = $this->translator->translate("ui.menuItems.dashboard");
    }
}