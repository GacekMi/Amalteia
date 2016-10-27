<?php

namespace App\Presenters;

use App\Model,
    Nette\Application\UI\Form,
    Nette,
    Nette\Application\UI\Multiplier,
    Nette\Utils\Html;


class OrderPresenter extends PrivatePresenter
{
    /** @inject @var \App\Model\Categories */
    public $categories;

    /** @inject @var \App\Model\Goods */
    public $goods;

    /** @inject @var \App\Model\Orders */
    public $orders;

    /** @inject @var \App\Model\OrderItems */
    public $orderItems;

    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->title = "Objednávka";
        $session = $this->getSession('basket');
    
        if($session->itemsBasket != null)
        {
            $keys = array();
            foreach ($session->itemsBasket as $key => $item)
            {
               $keys[]= $key;
            }

            $goodsDB = $this->goods->getGooods($keys);
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
                
                    //$good['currency'] = $this->goods->currency[$goodDB->currency];
                    $orderCurrency =  $this->goods->currency[$goodDB->currency];
                    //$good['availability'] = $this->goods->availability[$goodDB->availability];
                    //$goods[] = $good;
                }
            }

            $this->template->totalPrice =  $totalOrderPrice;
            $this->template->orderCurrency = $orderCurrency;
            $this->template->delp1 = 135;
            $this->template->delp1 = 160;
            $this->template->delp1 = 60;
            $this->template->feePay = 50;
            if($totalOrderPrice > 5000)
            {
                $this->template->feePay = 63;
            }
            
            $this->template->deliveryPay = 0;
            $this->template->feePay = 0;
        }

    }

    public function renderDefault()
    {
        if($this->getUser()->isLoggedIn())
        {
                $id = $this->getUser()->getIdentity()->getData()[\App\Model\Authenticator::COLUMN_ID];
                $editUser = $this->authenticator->get($id);
                $data = $editUser->toArray();
                unset($data[\App\Model\Authenticator::COLUMN_PASSWORD_HASH]);
                $this['orderForm']->setDefaults($data);
        }
    }

    protected function createComponentOrderForm() {
        $form = new Nette\Application\UI\Form;
        //Způsob odběru
        $form->addGroup('Způsob odběru')->setOption('container', Html::el('div')->class("col-lg-6"));
        $deliveryType = [
            '1' => 'Osobně',
            '2' => 'Osobní vyzvednutí v místě',
            '3' => 'Česká pošta',
            ];
        $form->addRadioList('deliveryType', '', $deliveryType)
            ->setDefaultValue('3')
            ->setAttribute('class', 'radio-button')
            ->addCondition(Form::IS_IN, array(1, 2))
                ->toggle("paymentType1")
            ->endCondition()
            ->addCondition(Form::EQUAL,'2')
                ->toggle("deliveryPlace")
            ->endCondition()
            ->addCondition(Form::EQUAL,'3')
                ->toggle("paymentType2")
                ->toggle(\App\Model\Orders::COLUMN_DELIVERY_LABEL)
                ->toggle(\App\Model\Orders::COLUMN_STREET)
                ->toggle(\App\Model\Orders::COLUMN_DELIVERY_ADD)
                ->toggle(\App\Model\Orders::COLUMN_TOWN)
                ->toggle(\App\Model\Orders::COLUMN_PSC)
                ->toggle('delivery_area')
                ->toggle('delivery_mail_type');

        $deliveryMailTypes = [
            '1' => 'Balík na poštu 135,- Kč',
            '2' => 'Balík do ruky  160,- Kč',

        ];

        $form->addSelect('deliveryMailType', 'Typ balíku', $deliveryMailTypes)
            ->setPrompt('Zvolte typ balíku')
            ->setOption('id', 'delivery_mail_type')
            ->addConditionOn($form['deliveryType'], Form::EQUAL, 3)
                        ->setRequired('Druh balíku musí být vybrán');

        $deliveryPlaces = [
            '1' => 'Ostrava',
            '2' => 'Frýdek-Místek',
            '3' => 'Nový Jičín',
            '4' => 'Olomouc',
            '5' => 'Hranice',
            '6' => 'Rožnov pod Radhoštěm',
            '7' => 'Vsetín',

        ];

        $form->addSelect('deliveryPlace', 'Místa vyzvednutí', $deliveryPlaces)
            ->setPrompt('Zvolte místo vyzvednutí')
            ->setOption('id', 'deliveryPlace')
            ->addConditionOn($form['deliveryType'], Form::EQUAL, 2)
                        ->setRequired('Místo vyzvednutí musí být vybráno');

        //Způsob platby
        $form->addGroup('Způsob platby')->setOption('container', Html::el('div')->class("col-lg-6"));
        $paymentType1 = [
            '1' => 'Hotově',
            '2' => 'Převodem na účet',
            ];
        $form->addRadioList('paymentType1', '', $paymentType1)
            ->setDefaultValue('2')
            ->setAttribute('class', 'radio-button')
            ->setOption('id', 'paymentType1');
        $paymentType2 = [
            '2' => 'Převodem na účet',
            '3' => 'Dobírka',
            ];
        $form->addRadioList('paymentType2', '', $paymentType2)
            ->setDefaultValue('2')
            ->setAttribute('class', 'radio-button')
            ->setOption('id', 'paymentType2');
    

        //Kontakntí údaje
        $form->addGroup('Kontaktní údaje')->setOption('container', Html::el('div')->class("col-lg-12 margin-top-30"));
            $form->addHidden("label1", NULL)
                    ->setOmitted(TRUE);
            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_FIRST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.firstName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                $form->addText(\App\Model\Orders::COLUMN_EMAIL, Html::el('span')->setText($this->translator->translate("ui.signMessage.email"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.emailMsg"))
                ->addRule(Form::EMAIL, $this->translator->translate("ui.signMessage.emailIncorect"));

            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_LAST_NAME, Html::el('span')->setText($this->translator->translate("ui.signMessage.lastName"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.lastNameMsg"));
                $form->addText(\App\Model\Orders::COLUMN_PHONE, Html::el('span')->setText($this->translator->translate("ui.signMessage.phone"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->setRequired($this->translator->translate("ui.signMessage.phoneMsg"))
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.phoneIncorect"), '^(\+420|\+421){1} {1}[1-9][0-9]{2} {1}[0-9]{3} {1}[0-9]{3}$');


        //Doručovací údaje
        $form->addGroup('Doručovací údaje')->setOption('container', Html::el('div')->class("col-lg-12 margin-top-30"))
            ->setOption('id', 'delivery_area');
        $form->addHidden("label2", NULL)
                    ->setOmitted(TRUE);
            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_DELIVERY_LABEL, Html::el('span')->setText('Jméno a příjmení nebo název')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                    ->setOption('id', \App\Model\Orders::COLUMN_DELIVERY_LABEL)
                    ->addConditionOn($form['deliveryType'], Form::EQUAL, 3)
                        ->setRequired('Jméno a příjmení nebo název musí být vyplněno');

                $form->addText(\App\Model\Orders::COLUMN_STREET, Html::el('span')->setText('Ulice a číslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                    ->setOption('id', \App\Model\Orders::COLUMN_STREET)
                    ->addConditionOn($form['deliveryType'], Form::EQUAL, 3)
                        ->setRequired('Ulice a číslo musí být vyplněno');

            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_DELIVERY_ADD, 'Upřesnění místa dodání')
                    ->setOption('id', \App\Model\Orders::COLUMN_DELIVERY_ADD);
                  
                $form->addText(\App\Model\Orders::COLUMN_TOWN, Html::el('span')->setText('Město')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->setOption('id', \App\Model\Orders::COLUMN_TOWN)
                        ->addConditionOn($form['deliveryType'], Form::EQUAL, 3)
                            ->setRequired('Město musí být vyplněno');
                    
            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_PSC, Html::el('span')->setText('PSČ')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->setOption('id', \App\Model\Orders::COLUMN_PSC)
                        ->addConditionOn($form['deliveryType'], Form::EQUAL, 3)
                            ->setRequired('PSČ musí být vyplněno');

        //Fakturační údaje
        $form->addGroup('Fakturační údaje')->setOption('container', Html::el('div')->class("col-lg-12 margin-top-30"));
            $form->addCheckbox('invoiceIsOpen', 'Chci zadat fakturační údaje pro doklady')
                    ->setOmitted(TRUE)
                    ->setAttribute('class', 'radio-button')
                    ->addCondition($form::EQUAL, TRUE)
                        ->toggle(\App\Model\Orders::COLUMN_INVOICE_LABEL)
                        ->toggle(\App\Model\Orders::COLUMN_INVOICE_STREET)
                        ->toggle(\App\Model\Orders::COLUMN_INVOICE_ICO)
                        ->toggle(\App\Model\Orders::COLUMN_INVOICE_TOWN)
                        ->toggle(\App\Model\Orders::COLUMN_INVOICE_DIC)
                        ->toggle(\App\Model\Orders::COLUMN_INVOICE_PSC);

             $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_LABEL, Html::el('span')->setText('Jméno a příjmení nebo název')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->setOption('id', \App\Model\Orders::COLUMN_INVOICE_LABEL)
                ->addConditionOn($form['invoiceIsOpen'], Form::EQUAL, TRUE)
                    ->setRequired('Jméno a příjmení nebo název musí být vyplněno');

                $form->addText(\App\Model\Orders::COLUMN_INVOICE_STREET, Html::el('span')->setText('Ulice a číslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->setOption('id', \App\Model\Orders::COLUMN_INVOICE_STREET)
                ->addConditionOn($form['invoiceIsOpen'], Form::EQUAL, TRUE)
                    ->setRequired('Ulice a číslo musí být vyplněno');
                

            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_ICO, 'IČO')
                    ->setOption('id', \App\Model\Orders::COLUMN_INVOICE_ICO);

                $form->addText(\App\Model\Orders::COLUMN_INVOICE_TOWN, Html::el('span')->setText('Město')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->setOption('id', \App\Model\Orders::COLUMN_INVOICE_TOWN)
                        ->addConditionOn($form['invoiceIsOpen'], Form::EQUAL, TRUE)
                            ->setRequired('Město musí být vyplněno');
                        
                    
            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_DIC, 'DIČ')
                    ->setOption('id', \App\Model\Orders::COLUMN_INVOICE_DIC);

                $form->addText(\App\Model\Orders::COLUMN_INVOICE_PSC, Html::el('span')->setText('PSČ')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->setOption('id', \App\Model\Orders::COLUMN_INVOICE_PSC)
                        ->addConditionOn($form['invoiceIsOpen'], Form::EQUAL, TRUE)
                            ->setRequired('PSČ musí být vyplněno');
                        

        

        //Doplňující údaje k objednávce
        $form->addGroup('Doplňující údaje k objednávce')->setOption('container', Html::el('div')->class("col-lg-12 margin-top-30"));
            $form->addHidden("label4", NULL)
                    ->setOmitted(TRUE);
            $form->addTextArea(\App\Model\Orders::COLUMN_DESCRIPTION, '');


        //$form->addSubmit('send', 'Odeslat objednávku');
       // $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        $form->addProtection($this->translator->translate("ui.signMessage.protectionMessage"));
        // call method signInFormSucceeded() on success
        $form->onSuccess[] = array($this, 'orderFormSucceeded');
        $form->onValidate[] = [$this, 'validateOrderForm'];        
        return $form;

    }
    //jmeno, prijmeni,tel, email, doplnujici info
    //adresa doruceni
    //adresa fakturacni , check box
    //platba na ucet, hotove
    //doprava
    //doprava ...balik, osobni, misto

    public function validateOrderForm($form)
    {
    }

    public function orderFormSucceeded($form, $values) {
            $values = $form->getValues(TRUE);
            $this->flashMessage('Objednávky ještě nejsou v provozu!', 'error');
    }
}