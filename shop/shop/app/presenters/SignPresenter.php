<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    Nette\Utils\Html;


class SignPresenter extends BasePresenter
{
     public function beforeRender() {
        parent::beforeRender();
        
    }

     /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    /** @persistent */
    public $backlink = '';


    /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm() {
        $form = new Nette\Application\UI\Form;
        $form->addText('username', Html::el('span')->setText('Jméno')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Zadejte vaše jméno');

        $form->addPassword('password', Html::el('span')->setText('Heslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Zadejte vaše heslo');

        $form->addCheckbox('remember', 'Pamatovat příhlášení');

        $form->addSubmit('send', 'Přihlásit');
//        $form->addReCaptcha('captcha')
//                ->addRule(Form::VALID, 'Ověřte prosím svou nerobotičnost.');
        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        // call method signInFormSucceeded() on success
        $form->onSuccess[] = array($this, 'signInFormSucceeded');
        return $form;
    }

    public function signInFormSucceeded($form, $values) {
        if ($values->remember) {
            $this->getUser()->setExpiration('14 days', FALSE);
        } else {
            $this->getUser()->setExpiration('20 minutes', TRUE);
        }

        try {
            $this->getUser()->login($values->username, $values->password);
            $this->user->getAuthenticator()->saveLoginDateTime($this->user->id);
            $this->restoreRequest($this->backlink);
            $this->redirect('Gallery:Default');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

   
        /**
     * Sign-reg form factory.
     * @return Nette\Application\UI\Form
     * $partnerId, $firstName, $lastName, $email, $phone, $birthdate, $password, $roles, $state, $personalId
     */
    protected function createComponentRegForm() {
        $form = new Nette\Application\UI\Form;

        $form->addText(\App\Model\Authenticator::COLUMN_PARTNER_ID, Html::el('span')->setText('Partner ID')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Zadejte číslo vašeho partnera');        
        $form->addText(\App\Model\Authenticator::COLUMN_FIRST_NAME, Html::el('span')->setText('Jméno')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Zadejte vaše jméno');
        $form->addText(\App\Model\Authenticator::COLUMN_LAST_NAME, Html::el('span')->setText('Příjmení')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Zadejte vaše příjmení');
        $form->addText(\App\Model\Authenticator::COLUMN_EMAIL, Html::el('span')->setText('E-mailová adresa')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Zadejte email')
                ->addRule(Form::EMAIL, 'Email nemá správný formát');
        $form->addText(\App\Model\Authenticator::COLUMN_PHONE, Html::el('span')->setText('Telefon'));
        $form->addText(\App\Model\Authenticator::COLUMN_BIRTH_DATE, Html::el('span')->setText('Datum narození ve tvaru d.m.yy'))
                ->setRequired(false)
                ->addRule(Form::PATTERN, 'Datum musí být ve tvaru d.m.yy', '([0-9]\s*){1,2}\.([0-9]\s*){1,2}\.([0-9]\s*){4}')
                ->setHtmlId('datepicker1');
        $form->addPassword(\App\Model\Authenticator::COLUMN_PASSWORD_HASH, Html::el('span')->setText('Heslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                    ->addRule(Form::FILLED, 'Heslo')
                    ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků', 8)
                    ->addRule(Form::PATTERN, 'Musí obsahovat číslici', '.*[0-9].*');
        $form->addPassword("confirm_password", Html::el('span')->setText('Znovu heslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                    ->setOmitted(TRUE)
                    ->addRule(Form::FILLED, "Potvrzovací heslo musí být vyplněné !")
                    ->addConditionOn($form[\App\Model\Authenticator::COLUMN_PASSWORD_HASH], Form::FILLED)
                    ->addRule(Form::EQUAL, "Hesla se musí shodovat !", $form[\App\Model\Authenticator::COLUMN_PASSWORD_HASH]);
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
        $form->addText(\App\Model\Authenticator::COLUMN_PERSONAL_ID, Html::el('span')->setText('Rodné číslo'))
                ->setRequired(false)
                ->addRule($verifyRC, 'Nesprávný formát rodného čísla');

        $form->addSubmit('send', 'Registrovat');
//        $form->addReCaptcha('captcha')
//                ->addRule(Form::VALID, 'Ověřte prosím svou nerobotičnost.');
        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu');
        // call method signInFormSucceeded() on success
        $form->onSuccess[] = array($this, 'regFormSucceeded');
        $form->onValidate[] = [$this, 'validateRegForm'];        
        return $form;
    }

    public function validateRegForm($form)
    {
        $values = $form->getValues();
        $id = $values[\App\Model\Authenticator::COLUMN_PARTNER_ID];
        if($id > 0)
        {
            $partnerID = $this->authenticator->get($id);
            if ($partnerID == null)
            {
                $form->addError('Neplatná identifikace partnera');
            }
        }

        $email = $values[\App\Model\Authenticator::COLUMN_EMAIL];
        $emailIsUnique = $this->authenticator->getByEmail($email);
            if ($emailIsUnique != null)//Amalteia.2016
            {
                $form->addError('Email je již registrován pod jiným uživatelem.');
            }

        
        $personalId = $values[\App\Model\Authenticator::COLUMN_PERSONAL_ID];
        if($personalId != null)
        {   
            $personalIdIsUnique = $this->authenticator->getByPersonalId($personalId);
            if ($personalIdIsUnique != null)
            {
                $form->addError('Partner s tímto rodným číslem je již registrován.');
            }    
        }
    }

    public function regFormSucceeded($form, $values) {
            $values = $form->getValues(TRUE);
            $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE] = \DateTime::createFromFormat('d.m.yy', $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE]);            
            $username = $values[\App\Model\Authenticator::COLUMN_FIRST_NAME];
            $this->authenticator->createUser($values);
            $this->flashMessage("Uživatel $username  byl vložen.", 'success');
            $this->redirect('default');
    }

    public function actionOut() {
        $this->getUser()->logout();
        $this->flashMessage('Odhlášení proběhlo vpořádku.', 'success');
        $this->redirect('in');
    }
}