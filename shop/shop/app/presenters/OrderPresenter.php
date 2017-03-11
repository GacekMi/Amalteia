<?php

namespace App\Presenters;

use App\Model,
    Nette\Application\UI\Form,
    Nette,
    Nette\Application\UI\Multiplier,
    Nette\Utils\Html,
    App\Controls\Grido\MyGrid;


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

    /** @var \Nette\Database\Context @inject */
    public $database;

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
            $this->template->feePayLimit = \App\Model\Orders::DOBIRKA_LIMIT;
            $this->template->delp1 = \App\Model\Orders::BALIK_NA_POSTU;
            $this->template->delp2 = \App\Model\Orders::BALIK_DO_RUKY;
            $this->template->delp3 = \App\Model\Orders::ODBER_V_MISTE;
            $this->template->feePay1 = \App\Model\Orders::DOBIRKA_1;
            $this->template->feePay2 = \App\Model\Orders::DOBIRKA_2;
            if($totalOrderPrice > \App\Model\Orders::DOBIRKA_LIMIT)
            {
                
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
        
        $form->addRadioList('deliveryType', '', $this->orders->deliveryType)
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

        $form->addSelect('deliveryMailType', 'Typ balíku', $this->orders->deliveryMailTypes)
            ->setPrompt('Zvolte typ balíku')
            ->setOption('id', 'delivery_mail_type')
            ->addConditionOn($form['deliveryType'], Form::EQUAL, 3)
                        ->setRequired('Druh balíku musí být vybrán');

        $form->addSelect('deliveryPlace', 'Místa vyzvednutí', $this->orders->deliveryPlaces)
            ->setPrompt('Zvolte místo vyzvednutí')
            ->setOption('id', 'deliveryPlace')
            ->addConditionOn($form['deliveryType'], Form::EQUAL, 2)
                        ->setRequired('Místo vyzvednutí musí být vybráno');

        //Způsob platby
        $form->addGroup('Způsob platby')->setOption('container', Html::el('div')->class("col-lg-6"));
        
        $form->addRadioList('paymentType1', '', $this->orders->paymentType1)
            ->setDefaultValue('2')
            ->setAttribute('class', 'radio-button')
            ->setOption('id', 'paymentType1');
       
        $form->addRadioList('paymentType2', '', $this->orders->paymentType2)
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
                    ->setOmitted(FALSE)
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

    public function validateOrderForm($form)
    {
    }

    public function orderFormSucceeded($form, $values) {
        $values = $form->getValues(TRUE);
        $ret = $this->createOrder($values); 
        if($this->getSession('basket') != null && $ret != FALSE)
        {
            unset($this->getSession('basket')->itemsBasket);
            $this->presenter->flashMessage('Vážený zákazníku, děkujeme za Vaši objednávku. Informace s platebními údaji Vám co nejdříve odešleme na Vámi uvedenou e-mailovou adresu. Zkontrolujte si zda Vám potřebné informace nebyly doručeny do hromadné pošty či spamu. O dodání zboží Vás budeme informovat. Váš AMALTEIA tým.', 'success');
            $this->redirect('Gallery:default');
            //$this->redirect('Order:sum');
        }
        else
        {
            $this->presenter->flashMessage('Něco se porouchalo.', 'error');
        }
    }

    private function createOrder($values)
    {
        $orderData = array();

        $orderData[\App\Model\Orders::COLUMN_STATE] = 1;
        $orderData[\App\Model\Orders::COLUMN_USER_STATE] = 1;
        $orderData[\App\Model\Orders::COLUMN_DATE] = new \DateTime();
        if($this->getUser()->isLoggedIn())
        {
             $id = $this->getUser()->getIdentity()->getData()[\App\Model\Authenticator::COLUMN_ID];
             $orderData[\App\Model\Orders::COLUMN_USER_ID] = $id;
        }
       
        //zpusob dopravy
        $orderData[\App\Model\Orders::COLUMN_ZP_O] = $values['deliveryType'];
        if($values['deliveryType']==2)
        {
            $orderData[\App\Model\Orders::COLUMN_ZP_M] = $values['deliveryPlace']*-1;
        }

        if($values['deliveryType']==3)
        {
            $orderData[\App\Model\Orders::COLUMN_ZP_M] = $values['deliveryMailType'];
        }

        //zpusob platby
        if($values['deliveryType']==3)
        {
             $orderData[\App\Model\Orders::COLUMN_ZP_P] = $values['paymentType2'];
        }
        else
        {
             $orderData[\App\Model\Orders::COLUMN_ZP_P] = $values['paymentType1'];
        }

        //zakladni udaje
        $orderData[\App\Model\Orders::COLUMN_FIRST_NAME] = $values[\App\Model\Orders::COLUMN_FIRST_NAME];
        $orderData[\App\Model\Orders::COLUMN_LAST_NAME] = $values[\App\Model\Orders::COLUMN_LAST_NAME];
        $orderData[\App\Model\Orders::COLUMN_EMAIL] = $values[\App\Model\Orders::COLUMN_EMAIL];
        $orderData[\App\Model\Orders::COLUMN_PHONE] = $values[\App\Model\Orders::COLUMN_PHONE];

        //adresa
        if($values['deliveryType']==3)
        {
            $orderData[\App\Model\Orders::COLUMN_DELIVERY_LABEL] = $values[\App\Model\Orders::COLUMN_DELIVERY_LABEL];
            $orderData[\App\Model\Orders::COLUMN_DELIVERY_ADD] = $values[\App\Model\Orders::COLUMN_DELIVERY_ADD];
            $orderData[\App\Model\Orders::COLUMN_STREET] = $values[\App\Model\Orders::COLUMN_STREET];
            $orderData[\App\Model\Orders::COLUMN_TOWN] = $values[\App\Model\Orders::COLUMN_TOWN];
            $orderData[\App\Model\Orders::COLUMN_PSC] = $values[\App\Model\Orders::COLUMN_PSC];
        }

        //fakturacni adresa
        if($values['invoiceIsOpen'])
        {
            $orderData[\App\Model\Orders::COLUMN_INVOICE_LABEL] = $values[\App\Model\Orders::COLUMN_INVOICE_LABEL];
            $orderData[\App\Model\Orders::COLUMN_INVOICE_ICO] = $values[\App\Model\Orders::COLUMN_INVOICE_ICO];
            $orderData[\App\Model\Orders::COLUMN_INVOICE_DIC] = $values[\App\Model\Orders::COLUMN_INVOICE_DIC];
            $orderData[\App\Model\Orders::COLUMN_INVOICE_STREET] = $values[\App\Model\Orders::COLUMN_INVOICE_STREET];
            $orderData[\App\Model\Orders::COLUMN_INVOICE_TOWN] = $values[\App\Model\Orders::COLUMN_INVOICE_TOWN];
            $orderData[\App\Model\Orders::COLUMN_INVOICE_PSC] = $values[\App\Model\Orders::COLUMN_INVOICE_PSC];
        }

        //description
        $orderData[\App\Model\Orders::COLUMN_DESCRIPTION] = $values[\App\Model\Orders::COLUMN_DESCRIPTION];
        return $this->addOrderItems($orderData);
    }

    private function addOrderItems($orderData)
    {
        $session = $this->getSession('basket');
    
        if($session->itemsBasket != null)
        {
            $keys = array();
            foreach ($session->itemsBasket as $key => $item)
            {
               $keys[]= $key;
            }

            $goodsDB = $this->goods->getGooods($keys);
            $items = array();
            $totalPrice = 0;
            $totalPriceVat = 0;
            foreach ($goodsDB as $goodDB)
            {
                if($goodDB->state == 'Z')
                {
                    $item = array();
                    $good = $goodDB->toArray();
                    $item[\App\Model\OrderItems::COLUMN_TYPE] = 1;
                    $item[\App\Model\OrderItems::COLUMN_GOOD_ID] = $goodDB->id;
                    $item[\App\Model\OrderItems::COLUMN_LABEL] = $goodDB->label;
                    $item[\App\Model\OrderItems::COLUMN_VAT] = $goodDB->vat;
                    $item[\App\Model\OrderItems::COLUMN_CURRENCY] = $goodDB->currency;
                    $item[\App\Model\OrderItems::COLUMN_UNIT] = $goodDB->unit;
                    $item[\App\Model\OrderItems::COLUMN_QUANTITY] = $session->itemsBasket[$goodDB->id];

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
                        $item[\App\Model\OrderItems::COLUMN_PRICE] = $goodDB->d_price;
                        $item[\App\Model\OrderItems::COLUMN_PRICE_VAT] = $goodDB->d_price_vat;
                    }
                    else
                    {
                        $item[\App\Model\OrderItems::COLUMN_PRICE] = $goodDB->price;
                        $item[\App\Model\OrderItems::COLUMN_PRICE_VAT] = $goodDB->price_vat;
                    }

                    $items[] = $item;
                    $totalPrice += $item[\App\Model\OrderItems::COLUMN_PRICE]* $item[\App\Model\OrderItems::COLUMN_QUANTITY];
                    $totalPriceVat += $item[\App\Model\OrderItems::COLUMN_PRICE_VAT]* $item[\App\Model\OrderItems::COLUMN_QUANTITY];
                }
            }
            //Celkova cena objednavky bez dopravy a platby
            $orderData[\App\Model\Orders::COLUMN_TOTAL_PRICE] = $totalPrice;
            $orderData[\App\Model\Orders::COLUMN_TOTAL_PRICE_VAT] = $totalPriceVat;
            //Doprava a platba

            if($orderData[\App\Model\Orders::COLUMN_ZP_O] == 3)
            {
               if($orderData[\App\Model\Orders::COLUMN_ZP_M]==1)
               {
                   //balik na postu
                   $orderData[\App\Model\Orders::COLUMN_DELIVERY_PAY] =  \App\Model\Orders::BALIK_NA_POSTU;
               }
               else
               {
                    //balik do ruky
                    $orderData[\App\Model\Orders::COLUMN_DELIVERY_PAY] =  \App\Model\Orders::BALIK_DO_RUKY;
               }
            }

            if($orderData[\App\Model\Orders::COLUMN_ZP_O] == 2)
            {
                //2 odber v miste
                $orderData[\App\Model\Orders::COLUMN_DELIVERY_PAY] =  \App\Model\Orders::ODBER_V_MISTE;
            }

            if($orderData[\App\Model\Orders::COLUMN_ZP_P] == 3)
            {
                //dobirka spocitat cenu
                $orderData[\App\Model\Orders::COLUMN_FEE_PAY] =  \App\Model\Orders::DOBIRKA_1;
                $tot = $orderData[\App\Model\Orders::COLUMN_TOTAL_PRICE_VAT] + $orderData[\App\Model\Orders::COLUMN_DELIVERY_PAY] + $orderData[\App\Model\Orders::COLUMN_FEE_PAY];
                if($tot > \App\Model\Orders::DOBIRKA_LIMIT)
                {
                    $orderData[\App\Model\Orders::COLUMN_FEE_PAY] =  \App\Model\Orders::DOBIRKA_2;
                }
            }

            return $this->orders->create($orderData, $items);
        }
         
        return 0;
    }

    
    protected function createComponentGridOrder($name) {
        $id = $this->getParameter('id');

        $grid = new MyGrid($this, $name);
        $grid->model = $this->database->table(\App\Model\Orders::TABLE_NAME);
        $grid->setDefaultSort(array(\App\Model\Orders::COLUMN_DATE => 'DESC'));
        $grid->translator->setLang('cs');
        $grid->addColumnText(\App\Model\Orders::COLUMN_ID, 'ID')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Orders::COLUMN_FIRST_NAME, 'Jméno')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Orders::COLUMN_LAST_NAME, 'Přijmení')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Orders::COLUMN_PHONE, 'Telefon')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Orders::COLUMN_EMAIL, 'Email')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Orders::COLUMN_DATE, 'Datum')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Orders::COLUMN_TOTAL_PRICE_VAT, 'Celkem')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();


        $grid->addColumnText(\App\Model\Orders::COLUMN_STATE, 'Stav')
                        ->setSortable()
                        ->setReplacement($this->orders->state)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Orders::COLUMN_STATE, 'Stav', $this->orders->state);

        $grid->addColumnText(\App\Model\Orders::COLUMN_USER_STATE, 'U-Stav')
                        ->setSortable()
                        ->setReplacement($this->orders->userState)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Orders::COLUMN_USER_STATE, 'U-Stav', $this->orders->userState);

        $grid->addColumnText(\App\Model\Orders::COLUMN_ZP_O, 'ZPO')
                        ->setSortable()
                        ->setReplacement($this->orders->deliveryType)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Orders::COLUMN_ZP_O, 'ZPO', $this->orders->deliveryType);

        $grid->addColumnText(\App\Model\Orders::COLUMN_ZP_M, 'ZPM')
                        ->setSortable()
                        ->setReplacement($this->orders->deliveryPlacesA)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Orders::COLUMN_ZP_M, 'ZPM', $this->orders->deliveryPlacesA);

        $grid->addColumnText(\App\Model\Orders::COLUMN_ZP_P, 'ZPP')
                        ->setSortable()
                        ->setReplacement($this->orders->paymentType)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Orders::COLUMN_ZP_P, 'ZPP', $this->orders->paymentType);
       
        $grid->filterRenderType = \Grido\Components\Filters\Filter::RENDER_INNER;

        $grid->addActionHref('goEdit', 'Detail');
          
        $grid->addActionHref('delete', 'Smazat');
        
        $grid->setExport();
    }
    // Akce gridu
    public function handleOperations($operation, $id) {
        if ($id) {
            $row = implode(', ', $id);
            $this->flashMessage("Provádím akce '$operation' pro řádky s  id: $row...", 'info');
        } else {
            $this->flashMessage('Nejsou vybrány žádné řádky.', 'error');
        }
        $this->flashMessage('Nejsou vybrány žádné řádky.', 'error');
        $this->redirect($operation, array('id' => $id));
    }

    public function actionGoEdit($id) {
        $this->redirect('Order:edit', $id);
    }

    public function actionDelete() {
        $id = $this->getParameter('id');
        $this->orders->delete($id);
        $this->flashMessage("Akce '$this->action' pro řádek s id: $id byla provedena.", 'success');
        $this->redirect('list');
    }

    public function renderEdit($id) {
        $order = $this->orders->get($id);
        $this->template->orderData = $order;
        $orderItems = $this->orderItems->getByOrder($id);
        $this->template->orderItemsData = $orderItems;
        $this->template->goodsObject = $this->goods;
        $this->template->ordersObject = $this->orders;
        $data = $order->toArray();
        $this['changeStateOrderForm']->setDefaults($data);
    }

    protected function createComponentChangeStateOrderForm() {
        $form = new Nette\Application\UI\Form;

        $form->addSelect(\App\Model\Orders::COLUMN_STATE, Html::el('span')->setText('Stav objednávky:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->orders->state)
            ->setPrompt('Zvolte stav objednávky')
            ->addRule(Form::FILLED, 'Vyplňte stav objednávky');
        $form->addSelect(\App\Model\Orders::COLUMN_USER_STATE, Html::el('span')->setText('Uživatelský stav objednávky:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->orders->userState)
            ->setPrompt('Zvolte uživatelský stav objednávky')
            ->addRule(Form::FILLED, 'Vyplňte uživatelský stav objednávky');

        $form->addSubmit('send', 'Změnit stav');
        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'span';
        $renderer->wrappers['pair']['container'] = Html::el('span')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        $form->addProtection($this->translator->translate("ui.signMessage.protectionMessage"));
        
        $form->onSuccess[] = array($this, 'changeStateOrderFormSucceeded');
        return $form;
    }

    public function changeStateOrderFormSucceeded($form, $values) {
        $values = $form->getValues(TRUE);
        $id = $this->getParameter('id');
        $this->orders->update($id, $values);
        $this->flashMessage($this->translator->translate("ui.signMessage.changeSaved"), 'success');
        $this->redirect('Order:list');
    }
}