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
            ->setAttribute('class', 'radio-button');

        //Způsob platby
        $form->addGroup('Způsob platby')->setOption('container', Html::el('div')->class("col-lg-6"));
        $paymentType = [
            '1' => 'Hotově',
            '2' => 'Převodem na účet',
            '3' => 'Dobírka',
            ];
        $form->addRadioList('paymentType', '', $paymentType)
            ->setAttribute('class', 'radio-button');

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
        $form->addGroup('Doručovací údaje')->setOption('container', Html::el('div')->class("col-lg-12 margin-top-30"));
        $form->addHidden("label2", NULL)
                    ->setOmitted(TRUE);
            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_DELIVERY_LABEL, Html::el('span')->setText('Jméno a příjmení nebo název')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                $form->addText(\App\Model\Orders::COLUMN_STREET, Html::el('span')->setText('Ulice a číslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.emailMsg"));

            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_DELIVERY_ADD, 'Upřesnění místa dodání')
                    ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                $form->addText(\App\Model\Orders::COLUMN_TOWN, Html::el('span')->setText('Město')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                    
            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_PSC, Html::el('span')->setText('PSČ')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));

        //Fakturační údaje
        $form->addGroup('Fakturační údaje')->setOption('container', Html::el('div')->class("col-lg-12 margin-top-30"));
            $form->addCheckbox('invoiceIsOpen', 'Chci zadat fakturační údaje pro doklady')
                    ->setOmitted(TRUE)
                    ->setAttribute('class', 'radio-button');
             $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_LABEL, Html::el('span')->setText('Jméno a příjmení nebo název')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_STREET, Html::el('span')->setText('Ulice a číslo')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.emailMsg"));

            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_ICO, 'IČO')
                    ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_TOWN, Html::el('span')->setText('Město')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                    
            $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-4"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_DIC, 'DIČ')
                    ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));
                $form->addText(\App\Model\Orders::COLUMN_INVOICE_PSC, Html::el('span')->setText('PSČ')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                        ->addRule(Form::FILLED, $this->translator->translate("ui.signMessage.firstNameMsg"));

        

        //Doplňující údaje k objednávce
        $form->addGroup('Doplňující údaje k objednávce')->setOption('container', Html::el('div')->class("col-lg-12 margin-top-30"));
            $form->addHidden("label4", NULL)
                    ->setOmitted(TRUE);
            $form->addTextArea(\App\Model\Orders::COLUMN_DESCRIPTION, '');


        $form->addSubmit('send', 'Odeslat objednávku');
        $form['send']->getControlPrototype()->class('btn btn-success');
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
    }
}