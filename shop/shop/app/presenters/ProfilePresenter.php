<?php

namespace App\Presenters;

use Nette;


class ProfilePresenter extends PrivatePresenter
{
    /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    public function beforeRender()
    {
        parent::beforeRender();
        $id = $this->getUser()->getIdentity()->getData()['id'];
        $this->template->userItem = $this->authenticator->get($id);
    }
}