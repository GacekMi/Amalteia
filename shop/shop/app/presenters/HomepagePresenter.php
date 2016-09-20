<?php

namespace App\Presenters;

use Nette;


class HomepagePresenter extends BasePresenter
{
    public function beforeRender()
    {

        parent::beforeRender();
            $template = $this->template;
            $template->name = 'Michal';
            $template->msg = $this->translator->translate("ui.title");
    }
}
