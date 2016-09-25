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
        $this->template->message = "Litujeme tato volba neni platna";
    }

public function renderCreateemail($id)
    {
         $this->template->token = 12356;
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
                $this->template->message = "Vas ucet byl aktivovan pokracujte zde";
            }
            else
            {
               $this->template->message = "Ou neco se porouchalo"; 
            }
        }
        else
        {
            $this->template->message = "Litujeme akci nelze provest";
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
            $this->template->message = "Litujeme";
        }
    }
}