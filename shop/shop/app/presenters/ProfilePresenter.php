<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    Nette\Utils\Html,
    Nette\Security\Passwords,
    Tracy\Debugger,
    Nette\Security;


class ProfilePresenter extends PrivatePresenter
{
    /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    public function beforeRender()
    {
        parent::beforeRender();
        $id = $this->getUser()->getIdentity()->getData()['id'];
        $this->template->userItem = $this->authenticator->get($id);
        $this->template->title = $this->translator->translate("ui.menuItems.profile");
    }

    protected function createComponentProfileForm() {
        $form = new Nette\Application\UI\Form;
       
        $form->addText(\App\Model\Authenticator::COLUMN_FIRST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.firstName")));
        $form->addText(\App\Model\Authenticator::COLUMN_LAST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.lastName")));
        $form->addText(\App\Model\Authenticator::COLUMN_EMAIL, Html::el('span')->setText($this->translator->translate("ui.signMessage.email")))
                ->setRequired(false)
                ->addRule(Form::EMAIL, $this->translator->translate("ui.signMessage.emailIncorect"));
        $form->addText(\App\Model\Authenticator::COLUMN_PHONE, Html::el('span')->setText($this->translator->translate("ui.signMessage.phone")));
        $form->addText(\App\Model\Authenticator::COLUMN_BIRTH_DATE, Html::el('span')->setText($this->translator->translate("ui.signMessage.birthDay")))
                ->setRequired(false)
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.birthDayIncorect"), '([0-9]\s*){1,2}\.([0-9]\s*){1,2}\.([0-9]\s*){4}')
                ->setHtmlId('datepicker1');
        $form->addPassword(\App\Model\Authenticator::COLUMN_PASSWORD_HASH, Html::el('span')->setText($this->translator->translate("ui.signMessage.password")))
                    ->setRequired(false)
                    ->addRule(Form::MIN_LENGTH, $this->translator->translate("ui.signMessage.passLenght", ['len' => 8]), 8)
                    ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.passPattern"), '.*[0-9].*');
        $form->addPassword("confirm_password", Html::el('span')->setText($this->translator->translate("ui.signMessage.confirmPass")))
                    ->setOmitted(TRUE)
                    ->setRequired(false)
                    ->addConditionOn($form[\App\Model\Authenticator::COLUMN_PASSWORD_HASH], Form::FILLED)
                    ->addRule(Form::EQUAL, $this->translator->translate("ui.signMessage.passNoEqual"), $form[\App\Model\Authenticator::COLUMN_PASSWORD_HASH]);
        $verifyRC = function ($rc, $arg) {
            // be liberal in what you receive
            $rc = $rc->value;
            if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
                return FALSE;
            }

            list(, $year, $month, $day, $ext, $c) = $matches;

            if ($c === '') {
                $year += $year < 54 ? 1900 : 1800;
            } else {
                // kontrolní číslice
                $mod = ($year . $month . $day . $ext) % 11;
                if ($mod === 10) $mod = 0;
                if ($mod !== (int) $c) {
                    return FALSE;
                }

                $year += $year < 54 ? 2000 : 1900;
            }

            // k měsíci může být připočteno 20, 50 nebo 70
            if ($month > 70 && $year > 2003) {
                $month -= 70;
            } elseif ($month > 50) {
                $month -= 50;
            } elseif ($month > 20 && $year > 2003) {
                $month -= 20;
            }

            // kontrola data
            if (!checkdate($month, $day, $year)) {
                return FALSE;
            }

            return TRUE;
        };
        $form->addText(\App\Model\Authenticator::COLUMN_PERSONAL_ID, Html::el('span')->setText($this->translator->translate("ui.signMessage.personalId")))
                ->setRequired(false)
                ->addRule($verifyRC, $this->translator->translate("ui.signMessage.personalIdIncorect"));
        $form->addSubmit('send', $this->translator->translate("ui.signMessage.changeButton"));
        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        $form->addProtection($this->translator->translate("ui.signMessage.protectionMessage"));
        // call method signInFormSucceeded() on success
        $form->onSuccess[] = array($this, 'profileFormSucceeded');
        $form->onValidate[] = [$this, 'validateProfileForm'];        
        return $form;
    }

    public function validateProfileForm($form)
    {
        $values = $form->getValues();

        $email = $values[\App\Model\Authenticator::COLUMN_EMAIL];
        if($email != null)
        { 
            $emailIsUnique = $this->authenticator->getByEmail($email);
            if ($emailIsUnique != null)
            {
                $form->addError($this->translator->translate("ui.signMessage.duplicitEmail"));
            }
        }

        $personalId = $values[\App\Model\Authenticator::COLUMN_PERSONAL_ID];
        if($personalId != null)
        {   
            $personalIdIsUnique = $this->authenticator->getByPersonalId($personalId);
            if ($personalIdIsUnique != null)
            {
                $form->addError($this->translator->translate("ui.signMessage.duplicitPartnerId"));
            }    
        }
    }

    public function profileFormSucceeded($form, $values) {
            $values = $form->getValues(TRUE);
            
            $id = $this->getUser()->getIdentity()->getData()['id'];

            //pouze pokud je vyplneno jinak odstran z aktualizace
            $firstName = $values[\App\Model\Authenticator::COLUMN_FIRST_NAME];
            if($firstName == null)
            {
                unset($values[\App\Model\Authenticator::COLUMN_FIRST_NAME]);
            }

            $lastName = $values[\App\Model\Authenticator::COLUMN_LAST_NAME];
            if($lastName == null)
            {
                unset($values[\App\Model\Authenticator::COLUMN_LAST_NAME]);
            }

            $email = $values[\App\Model\Authenticator::COLUMN_EMAIL];
            if($email == null)
            {
                unset($values[\App\Model\Authenticator::COLUMN_EMAIL]);
            }

            $phone = $values[\App\Model\Authenticator::COLUMN_PHONE];
            if($phone == null)
            {
                unset($values[\App\Model\Authenticator::COLUMN_PHONE]);
            }

            $birth = $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE];
            if($birth != null)
            {
                $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE] = \DateTime::createFromFormat('d.m.yy', $birth); 
            }
            else{
                unset($values[\App\Model\Authenticator::COLUMN_BIRTH_DATE]);
            }

            $pass = $values[\App\Model\Authenticator::COLUMN_PASSWORD_HASH];
            if($pass != null)
            { 
                $values[\App\Model\Authenticator::COLUMN_PASSWORD_HASH] = Passwords::hash($pass);   
            }   
            else{
                unset($values[\App\Model\Authenticator::COLUMN_PASSWORD_HASH]);
            }  

            $personalId = $values[\App\Model\Authenticator::COLUMN_PERSONAL_ID];
            if($personalId == null)
            {
                unset($values[\App\Model\Authenticator::COLUMN_PERSONAL_ID]);
            }  
            
            $this->authenticator->update($id, $values);
            $this->flashMessage($this->translator->translate("ui.signMessage.changeSaved"), 'success');
            $this->redirect('default');
    }
}