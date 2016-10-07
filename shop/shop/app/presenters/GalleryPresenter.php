<?php

namespace App\Presenters;

use Nette;


class GalleryPresenter extends BasePresenter
{
    public function beforeRender()
    {
         parent::beforeRender();
         $this->template->title = $this->translator->translate("ui.menuItems.gallery");
    }
}
