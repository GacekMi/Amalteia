<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    App\Controls\Grido\MyGrid,
    Nette\Utils\Html;


class BasketPresenter extends PrivatePresenter
{
    /** @inject @var \App\Model\Goods */
    public $goods;

    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->title = "Košík";
    }

    public function renderDefault()
    {
        $session = $this->getSession('basket');
        $basketIsEmpty = true;
        if($session->itemsBasket != null)
        {
            $basketIsEmpty = false;
            $keys = array();
            foreach ($session->itemsBasket as $key => $item)
            {
               $keys[]= $key;
            }

            $goodsDB = $this->goods->getGooods($keys);
            $goods = [];
            $totalOrderPrice = 0;
            $orderCurrency = "";
            foreach ($goodsDB as $goodDB)
            {
                if($goodDB->state == 'Z')
                {
                    $good = $goodDB->toArray();
                    $good['count'] = $session->itemsBasket[$goodDB->id];
                    //cena dle prihlaseneho uzivatele
                    $isVIP = false;

                    if($this->getUser()->getIdentity()!=null && $this->getUser()->isLoggedIn())
                    {
                        $vipData = $this->getUser()->getIdentity()->getData()['vip_date'];
                        
                        if(date("Y-m-d") <= $vipData)
                        {
                            $isVIP = true;
                        }
                    }
                
                    if ($this->getUser()->isLoggedIn()&&($this->getUser()->isInRole('partner') || $isVIP))
                    {
                        $good['price'] = $goodDB->d_price_vat;
                    }
                    else
                    {
                        $good['price'] = $goodDB->price_vat;
                    }

                    $good['total_price'] = $good['price'] * $good['count'];
                    $totalOrderPrice += $good['total_price'];
                    $good['unit'] = $this->goods->unit[$goodDB->unit];
                    $good['currency'] = $this->goods->currency[$goodDB->currency];
                    $orderCurrency = $good['currency'];
                    $good['availability'] = $this->goods->availability[$goodDB->availability];
                    $goods[] = $good;
                }
            }
            $this->template->goods =  $goods;
            $this->template->totalOrderPrice =  $totalOrderPrice;
            $this->template->orderCurrency = $orderCurrency;
            $this->template->orderPage = "Basket:in";
            if($this->getUser()->isLoggedIn()) $this->template->orderPage = "Order:default";
        }

        $this->template->basketIsEmpty = $basketIsEmpty;
    }

    public function renderPlusItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              $this->getSession('basket')->itemsBasket[$id]++;
        }

        $this->redirect('default');
    }

    public function renderMinusItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              $this->getSession('basket')->itemsBasket[$id]--;
              if($this->getSession('basket')->itemsBasket[$id]< 1)
              {
                   unset($this->getSession('basket')->itemsBasket[$id]);
              }
        }

        $this->redirect('default');
    }

    public function renderRemoveItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              unset($this->getSession('basket')->itemsBasket[$id]);
              $this->presenter->flashMessage('Zbozi bylo odebráno z kosiku.', 'success');
        }

        $this->redirect('default');
    }

    public function renderChangeCountItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              $count = $this->getParameter('count');
              if($count < 1)
              {
                unset($this->getSession('basket')->itemsBasket[$id]);
                $this->presenter->flashMessage('Zbozi bylo odebráno z kosiku.', 'success');
              }
              else{
                  $this->getSession('basket')->itemsBasket[$id] = $count;
                  $this->presenter->flashMessage('U zbozi byl upraven počet položek v kosiku.', 'success');
              }
        }
        $this->redirect('default');
    }

    public function renderDeleteBasket()
    {
        if($this->getSession('basket') != null)
        {
              unset($this->getSession('basket')->itemsBasket);
              $this->presenter->flashMessage('Kosik vymazán.', 'success');
        }

        $this->redirect('Gallery:default');
    }

     /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm() {
        $form = new Nette\Application\UI\Form;
        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));
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
            //$this->restoreRequest($this->backlink);
            $this->redirect('Order:Default');
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

        $form[\App\Model\Authenticator::COLUMN_PARTNER_ID]->getControlPrototype()->setAttribute('data-toggle', 'popover');
        $form[\App\Model\Authenticator::COLUMN_PARTNER_ID]->getControlPrototype()->setAttribute('title', 'Referenční ID doporučitele');
        $form[\App\Model\Authenticator::COLUMN_PARTNER_ID]->getControlPrototype()->setAttribute('data-content', 'Je uvedeno na létáčku popřípadě vám jej sdělí váš doporučitel. V případě že, neznáte doporučitele a nemáte letáček, uveďťe prosím jako referenční číslo 369000000.');

        $form->addText(\App\Model\Authenticator::COLUMN_FIRST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.firstName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_LAST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.lastName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.lastNameMsg"));
        $form->addText(\App\Model\Authenticator::COLUMN_EMAIL, Html::el('span')->setText($this->translator->translate("ui.signMessage.email"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.emailMsg"))
                ->addRule(Form::EMAIL, $this->translator->translate("ui.signMessage.emailIncorect"));

        //$form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));

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
        //$form->addReCaptcha('captcha', NULL, $this->translator->translate("ui.signMessage.reCaptchaMessage"));
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
        if($id > 369000000 || $id < 369000000)
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

}