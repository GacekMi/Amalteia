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

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));
        $form->addText(\App\Model\Authenticator::COLUMN_PARTNER_ID, Html::el('span')->setText($this->translator->translate("ui.signMessage.partnerId"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.partnerIdMsg"));        
        $form->addText(\App\Model\Authenticator::COLUMN_FIRST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.firstName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_LAST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.lastName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.lastNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_EMAIL, Html::el('span')->setText($this->translator->translate("ui.signMessage.email"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.emailMsg"))
                ->addRule(Form::EMAIL, $this->translator->translate("ui.signMessage.emailIncorect"));

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));

        $form->addText(\App\Model\Authenticator::COLUMN_PHONE, Html::el('span')->setText($this->translator->translate("ui.signMessage.phone"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->setRequired($this->translator->translate("ui.signMessage.phoneMsg"))
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.phoneIncorect"), '^(\+420|\+421){1} {1}[1-9][0-9]{2} {1}[0-9]{3} {1}[0-9]{3}$');

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
        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-12"));
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
        if($id > 936000000 || $id < 936000000)
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

        $phone = $values[\App\Model\Authenticator::COLUMN_PHONE];
        $phoneIsUnique = $this->authenticator->getByPhone($phone);
            if ($phoneIsUnique != null)
            {
                $form->addError($this->translator->translate("ui.signMessage.duplicitPhone"));
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