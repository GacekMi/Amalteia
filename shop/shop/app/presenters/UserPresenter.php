<?php

namespace App\Presenters;

use Nette;


class UserPresenter extends PrivatePresenter
{
     /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    public function beforeRender()
    {

        parent::beforeRender();
        $this->template->users = $this->authenticator->getList();
    }
}