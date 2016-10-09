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
        $this->template->title = $this->translator->translate("ui.menuItems.logIn");
    }

    public function renderDefault()
    {
        $this->redirect('Gallery:default');

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
        $form->addText('username', Html::el('span')->setText($this->translator->translate("ui.signMessage.userName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.userNameMsg"));

        $form->addPassword('password', Html::el('span')->setText($this->translator->translate("ui.signMessage.password"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.passwordMsg"));

        $form->addCheckbox('remember', $this->translator->translate("ui.signMessage.remeberLogin"));

        $form->addSubmit('send', $this->translator->translate("ui.signMessage.loginButton"));
        $form->addReCaptcha('captcha', NULL, $this->translator->translate("ui.signMessage.reCaptchaMessage"));
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

        $form->addText(\App\Model\Authenticator::COLUMN_PARTNER_ID, Html::el('span')->setText($this->translator->translate("ui.signMessage.partnerId"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.partnerIdMsg"));        
        $form->addText(\App\Model\Authenticator::COLUMN_FIRST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.firstName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_LAST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.lastName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.lastNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_EMAIL, Html::el('span')->setText($this->translator->translate("ui.signMessage.email"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.emailMsg"))
                ->addRule(Form::EMAIL, $this->translator->translate("ui.signMessage.emailIncorect"));
        $form->addText(\App\Model\Authenticator::COLUMN_PHONE, Html::el('span')->setText($this->translator->translate("ui.signMessage.phone")));
        $form->addText(\App\Model\Authenticator::COLUMN_BIRTH_DATE, Html::el('span')->setText($this->translator->translate("ui.signMessage.birthDay")))
                ->setRequired(false)
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.birthDayIncorect"), '([0-9]\s*){1,2}\.([0-9]\s*){1,2}\.([0-9]\s*){4}')
                ->setHtmlId('datepicker1');
        $form->addPassword(\App\Model\Authenticator::COLUMN_PASSWORD_HASH, Html::el('span')->setText($this->translator->translate("ui.signMessage.password"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                    ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.passMsg"))
                    ->addRule(Form::MIN_LENGTH, $this->translator->translate("ui.signMessage.passLenght", ['len' => 8]), 8)
                    ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.passPattern"), '.*[0-9].*');
        $form->addPassword("confirm_password", Html::el('span')->setText($this->translator->translate("ui.signMessage.confirmPass"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                    ->setOmitted(TRUE)
                    ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.confirmPassMsg"))
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
        $form->addCheckbox(\App\Model\Authenticator::AGREE_TERM_CON, $this->translator->translate("ui.signMessage.agreeTermsCon"))
                ->setOmitted(TRUE)
                ->setRequired($this->translator->translate("ui.signMessage.agreeTermsConMsg"));
        $form->addSubmit('send', $this->translator->translate("ui.signMessage.registerButton"));
        $form->addReCaptcha('captcha', NULL, $this->translator->translate("ui.signMessage.reCaptchaMessage"));
        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        $form->addProtection($this->translator->translate("ui.signMessage.protectionMessage"));
        // call method signInFormSucceeded() on success
        $form->onSuccess[] = array($this, 'regFormSucceeded');
        $form->onValidate[] = [$this, 'validateRegForm'];        
        return $form;
    }

    public function validateRegForm($form)
    {
        $values = $form->getValues();
        //$agree = $values[\App\Model\Authenticator::AGREE_TERM_CON];

        $id = $values[\App\Model\Authenticator::COLUMN_PARTNER_ID];
        if($id > 0)
        {
            $partnerID = $this->authenticator->get($id);
            if ($partnerID == null)
            {
                $form->addError($this->translator->translate("ui.signMessage.partnerNoExist"));
            }
        }

        $email = $values[\App\Model\Authenticator::COLUMN_EMAIL];
        $emailIsUnique = $this->authenticator->getByEmail($email);
            if ($emailIsUnique != null)
            {
                $form->addError($this->translator->translate("ui.signMessage.duplicitEmail"));
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

    public function regFormSucceeded($form, $values) {
            $values = $form->getValues(TRUE);
            $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE] = \DateTime::createFromFormat('d.m.yy', $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE]);            
            $username = $values[\App\Model\Authenticator::COLUMN_FIRST_NAME];
            $template = $this->createTemplate();
            $this->authenticator->createUser($values, $template, $this->translator->getLocale());
            $this->flashMessage($this->translator->translate("ui.signMessage.userCreated", ['name' => $username]), 'success');
            $this->redirect('default');
    }

    public function actionOut() {
        $this->getUser()->logout();
        $this->flashMessage($this->translator->translate("ui.signMessage.logOutOK"), 'success');
        $this->redirect('in');
    }
}