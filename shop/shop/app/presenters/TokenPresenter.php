<?php

namespace App\Presenters;

use Nette;

class TokenPresenter extends BasePresenter
{
    /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    public function beforeRender()
    {

        parent::beforeRender();
    }

    public function renderDefault()
    {
        $this->redirect('Gallery:default');
    }

    public function renderActivate($id)
    {
        if($id == null) $id = -1;
        $userItem = $this->authenticator->getUserByToken($id);
        if($userItem != null)
        {
            $type = $userItem[\App\Model\Authenticator::COLUMN_TOKEN_TYPE];
            if($type == 1)
            {
                $this->authenticator->setUserState($userItem[\App\Model\Authenticator::COLUMN_ID], 1);
                $values = array(\App\Model\Authenticator::COLUMN_TOKEN => NULL, \App\Model\Authenticator::COLUMN_TOKEN_TYPE => NULL);
                $this->authenticator->update($userItem[\App\Model\Authenticator::COLUMN_ID], $values);
                $this->template->message = $this->translator->translate("ui.tokenMessage.userIsVerified");
            }
            else
            {
               $this->template->message = $this->translator->translate("ui.tokenMessage.typeIsIncorect"); 
            }
        }
        else
        {
            $this->template->message = $this->translator->translate("ui.tokenMessage.userNotFound");
        } 
    }

    public function renderResetPass($id)
    {
        if($id == null) $id = -1;
        $userItem = $this->authenticator->getUserByToken($id);
        if($userItem != null)
        {
            $this->template->message = $userItem[\App\Model\Authenticator::COLUMN_FIRST_NAME] . "  ".  $userItem[\App\Model\Authenticator::COLUMN_TOKEN_TYPE]  ;
        }
        else
        {
             $this->template->message = $this->translator->translate("ui.tokenMessage.userNotFound");
        }
    }
}