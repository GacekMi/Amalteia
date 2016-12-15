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
        $id = $this->getUser()->getIdentity()->getData()[\App\Model\Authenticator::COLUMN_ID];
        $editUser = $this->authenticator->get($id);
        $this->template->userItem =  $editUser;
        $data = $editUser->toArray();
        unset($data[\App\Model\Authenticator::COLUMN_PASSWORD_HASH]);
        $data[\App\Model\Authenticator::COLUMN_BIRTH_DATE] = $data[\App\Model\Authenticator::COLUMN_BIRTH_DATE]->format('d.m.Y');
        $this['profileForm']->setDefaults($data);
        $this->template->title = $this->translator->translate("ui.menuItems.profile");
    }


    //  ZMENA HESLA
    protected function createComponentPassUserForm() {
        $userId = $this->getParameter('id');

        $form = new Nette\Application\UI\Form;

        $form->addPassword(\App\Model\Authenticator::COLUMN_PASSWORD_HASH, Html::el('span')->setText('Původní heslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Původní heslo');

        $form->addPassword('new_password', Html::el('span')->setText('Nové heslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Nové heslo')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků', 8)
                ->addRule(Form::PATTERN, 'Musí obsahovat číslici', '.*[0-9].*');
        $form->addPassword("confirm_password", Html::el('span')->setText('Potvrzení nového hesla')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, "Potvrzení nového hesla musí být vyplněné !")
                ->addConditionOn($form["new_password"], Form::FILLED)
                ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form["new_password"]);


        $form->addSubmit('send', 'Změnit heslo');


        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;

        $form->onSuccess[] = array($this, 'passUserFormSucceeded');
        return $form;
    }

    public function PassUserFormSucceeded($form, $values) {
        $id = $this->getUser()->getIdentity()->getData()['id'];
        $editUser = $this->authenticator->get($id);
        $data = $editUser->toArray();
        $values = $form->getValues(TRUE);
        $resultPasswd = $this->authenticator->verifiPassword($values[\App\Model\Authenticator::COLUMN_PASSWORD_HASH], $data[\App\Model\Authenticator::COLUMN_PASSWORD_HASH]);
        if (!$resultPasswd) {
            $this->flashMessage("Vaše heslo není zprávné.", 'error');
        } else {
            $this->authenticator->changePassword($id, $values['new_password']);
            $this->flashMessage("Vaše heslo bylo změněno. Změny se projeví až po opětovném přihlášení.", 'success');
            $this->redirect('default');
        }
    }


    // ZMENA UZIVATELSKEHO PROFILU
     protected function createComponentProfileForm() {
        $form = new Nette\Application\UI\Form;
        $form->addText(\App\Model\Authenticator::COLUMN_FIRST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.firstName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_LAST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.lastName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.lastNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_EMAIL, Html::el('span')->setText($this->translator->translate("ui.signMessage.email"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.emailMsg"))
                ->addRule(Form::EMAIL, $this->translator->translate("ui.signMessage.emailIncorect"));
        $form->addText(\App\Model\Authenticator::COLUMN_PHONE, Html::el('span')->setText($this->translator->translate("ui.signMessage.phone"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->setRequired($this->translator->translate("ui.signMessage.phoneMsg"))
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.phoneIncorect"), '^(\+420|\+421){1} {1}[1-9][0-9]{2} {1}[0-9]{3} {1}[0-9]{3}$');

        $form->addText(\App\Model\Authenticator::COLUMN_BIRTH_DATE, Html::el('span')->setText($this->translator->translate("ui.signMessage.birthDay")))
                ->setRequired(false)
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.birthDayIncorect"), '([0-9]\s*){1,2}\.([0-9]\s*){1,2}\.([0-9]\s*){4}')
                ->setHtmlId('datepicker1');
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
        $id = $this->getUser()->getIdentity()->getData()['id'];

        $email = $values[\App\Model\Authenticator::COLUMN_EMAIL];
        if($email != null)
        { 
            $emailIsUnique = $this->authenticator->getByEmail($email);
            if ($emailIsUnique != null && $emailIsUnique->id != $id)
            {
                $form->addError($this->translator->translate("ui.signMessage.duplicitEmail"));
            }
        }

        $phone = $values[\App\Model\Authenticator::COLUMN_PHONE];
        $phoneIsUnique = $this->authenticator->getByPhone($phone);
            if ($phoneIsUnique != null && $phoneIsUnique->id != $id)
            {
                $form->addError($this->translator->translate("ui.signMessage.duplicitPhone"));
            }
    }

     public function profileFormSucceeded($form, $values) {
            $values = $form->getValues(TRUE);
            $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE] = \DateTime::createFromFormat('d.m.Y', $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE]);   
            $id = $this->getUser()->getIdentity()->getData()['id'];
            
            $this->authenticator->update($id, $values);
            $this->flashMessage($this->translator->translate("ui.signMessage.changeSaved"), 'success');
            $this->redirect('default');
    }
}
















   
    

   