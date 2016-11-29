<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    App\Controls\Grido\MyGrid,
    Nette\Utils\Html;


class UserPresenter extends PrivatePresenter
{
     /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    /** @var \Nette\Database\Context @inject */
    public $database;

    public function beforeRender()
    {

        parent::beforeRender();
        $this->template->users = $this->authenticator->getList();
        $this->template->title = $this->translator->translate("ui.menuItems.user");
    }

    public function renderDefault($id) {
          $this->template->isSelect = False;
          if($id > 0){$this->template->isSelect = True;}
    }

    public function renderEdit($id) {
        $editUser = $this->authenticator->get($id);
        $this->template->userItem =  $editUser;
        if (!$editUser) {
            
        } else {
            if ($this->authenticator->isAdminRow($id, $this->getUser()->getIdentity()->getData()['role'])) {
                $this->flashMessage('Nemáte právo na změnu uživatele admin.', 'error');
                $this->redirect('default');
            } else {
                $data = $editUser->toArray();
                unset($data[\App\Model\Authenticator::COLUMN_PASSWORD_HASH]);
                if(isset($data[\App\Model\Authenticator::COLUMN_BIRTH_DATE]))
                {
                    $data[\App\Model\Authenticator::COLUMN_BIRTH_DATE] = $data[\App\Model\Authenticator::COLUMN_BIRTH_DATE]->format('d.m.Y');
                }
                
                if(isset($data[\App\Model\Authenticator::COLUMN_VIP_DATE]))
                {
                    $data[\App\Model\Authenticator::COLUMN_VIP_DATE] = $data[\App\Model\Authenticator::COLUMN_VIP_DATE]->format('d.m.Y');
                }
                
                $data[\App\Model\Authenticator::COLUMN_ROLE] = explode(',', $data[\App\Model\Authenticator::COLUMN_ROLE]);
                $this['editUserForm']->setDefaults($data);
            }
        }
    }

    protected function createComponentGrid($name) {
        $id = $this->getParameter('id');
        $isSelect = false;
        if (isset($id) && $id > 0) {
            $isSelect = true;
        }

        $grid = new MyGrid($this, $name);
        $grid->model = $this->database->table(\App\Model\Authenticator::TABLE_NAME);

        $grid->translator->setLang('cs');
        $grid->addColumnText(\App\Model\Authenticator::COLUMN_ID, $this->translator->translate("ui.signMessage.id"))
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Authenticator::COLUMN_PARTNER_ID, 'Ref. ID')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Authenticator::COLUMN_FIRST_NAME, $this->translator->translate("ui.signMessage.firstName"))
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Authenticator::COLUMN_LAST_NAME, $this->translator->translate("ui.signMessage.lastName"))
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnEmail(\App\Model\Authenticator::COLUMN_EMAIL, 'Email')
                ->setSortable()
                ->setFilterText();
        $grid->getColumn(\App\Model\Authenticator::COLUMN_EMAIL)->cellPrototype->class[] = 'center';
        $grid->addColumnText(\App\Model\Authenticator::COLUMN_PHONE, $this->translator->translate("ui.signMessage.phone"))
                ->setSortable()
                ->setFilterText();
        $grid->getColumn(\App\Model\Authenticator::COLUMN_PHONE)->cellPrototype->class[] = 'center';
        $grid->addColumnDate(\App\Model\Authenticator::COLUMN_BIRTH_DATE, 'Dat. nar.', \Grido\Components\Columns\Date::FORMAT_DATE)
                ->setSortable()
                ->setFilterDateRange()
                ->setCondition([$this, 'secondCDateFilterCondition']);
       // $grid->getColumn(\App\Model\Authenticator::COLUMN_BIRTH_DATE)->headerPrototype->style = 'width: 100px';
      //  $grid->getColumn(\App\Model\Authenticator::COLUMN_BIRTH_DATE)->cellPrototype->style = 'width: 100px';

        $grid->addColumnDate(\App\Model\Authenticator::COLUMN_VIP_DATE, 'VIP', \Grido\Components\Columns\Date::FORMAT_DATE)
                ->setSortable()
                ->setFilterDateRange()
                ->setCondition([$this, 'secondCDateFilterCondition']);
        $grid->getColumn(\App\Model\Authenticator::COLUMN_VIP_DATE)->cellPrototype->class[] = 'center';

        $grid->addColumnText(\App\Model\Authenticator::COLUMN_TOKEN_TYPE, 'Typ tokenu')
                        ->setSortable()
                        ->setReplacement(array(0 => 'Není', 1 => 'Aktivace'))
                ->cellPrototype->class[] = 'center';

        $grid->addFilterSelect(\App\Model\Authenticator::COLUMN_TOKEN_TYPE, 'Typ tokenu', array(
            '' => '',
            0 => 'Není',
            1 => 'Aktivace',
            2 => 'Heslo'
        ));

        $grid->addColumnText(\App\Model\Authenticator::COLUMN_ROLE, 'Role')
                ->setSortable()
                ->setFilterText();
        $grid->getColumn(\App\Model\Authenticator::COLUMN_ROLE)->cellPrototype->class[] = 'center';

        $grid->addColumnDate(\App\Model\Authenticator::COLUMN_REGISTERED, 'Registrován', \Grido\Components\Columns\Date::FORMAT_DATETIME)
                ->setSortable()
                ->setFilterDateRange()
                ->setCondition([$this, 'lastloginFilterCondition']);
        $grid->getColumn(\App\Model\Authenticator::COLUMN_REGISTERED)->cellPrototype->class[] = 'center';

        $grid->addColumnDate(\App\Model\Authenticator::COLUMN_LAST_LOGIN, 'Přihlášen', \Grido\Components\Columns\Date::FORMAT_DATETIME)
                ->setSortable()
                ->setFilterDate()
                ->setCondition([$this, 'lastloginFilterCondition']);
        $grid->getColumn(\App\Model\Authenticator::COLUMN_LAST_LOGIN)->cellPrototype->class[] = 'center';

        $grid->addColumnText(\App\Model\Authenticator::COLUMN_STATE, 'Stav')
                        ->setSortable()
                        ->setReplacement(array(0 => Html::el('b')->setText('Blokován'), 1 => 'Aktivní'))
                ->cellPrototype->class[] = 'center';

        $grid->addFilterSelect(\App\Model\Authenticator::COLUMN_STATE, 'Stav', array(
            '' => '',
            0 => 'Blokován',
            1 => 'Aktivní'
        ));

        $grid->filterRenderType = \Grido\Components\Filters\Filter::RENDER_OUTER;

        //pozustatek y bajsu kdyz je potreba presmerovat a nekoho proradit jako kartoteka a zaznamu v ni se meni vlastnik tak se pouzije toto
        if ($isSelect) {
            $grid->addActionHref('selectuser', 'Vybrat', NULL, array('company_id'=>$id))
                    ->setIcon('pencil');
        } else {
            $grid->addActionHref('goEdit', 'Edit')
                    ->setIcon('ok')
                    ->setDisable(function($item) {
                        if (strpos($item->role, "admin")) {
                            if (!strpos($this->getUser()->getIdentity()->getData()['role'], 'admin')) {
                                return true;
                            }
                        }
                        return false;
                    });

            $grid->addActionHref('data', 'Profil')
                    ->setIcon('info-sign')
                    ->setDisable(function($item) {
                        if (strpos($item->role, "admin")) {
                            if (!strpos($this->getUser()->getIdentity()->getData()['role'], 'admin')) {
                                return true;
                            }
                        }
                        return false;
                    })
                    ->setConfirm(function($item) {
                        return "Tabulka uživatélů zatím nemá implementované rozšířené data.";
                    });

            $grid->addActionHref('reset', 'Reset hesla')
                    ->setIcon('registration-mark')
                    ->setDisable(function($item) {
                        if (strpos($item->role, "admin")) {
                            if (!strpos($this->getUser()->getIdentity()->getData()['role'], 'admin')) {
                                return true;
                            }
                        }
                        return false;
                    })
                    ->setConfirm(function($item) {
                        return "Opravdu si přejete tomuto uživateli resetovat heslo?";
                    });

           /* $grid->addActionHref('delete', 'Smazat')
                    ->setIcon('trash')
                    ->setDisable(function($item) {
                        if (strpos($item->role, "admin")) {
                            if (!strpos($this->getUser()->getIdentity()->getData()['role'], 'admin')) {
                                return true;
                            }
                        }
                        return false;
                    })
                    ->setConfirm(function($item) {
                        return "Jste si jisti smazáním účtu {$item->email} ?";
                    });*/

            $grid->addActionHref('block', 'Blokovat')
                    ->setIcon('ban-circle')
                    ->setDisable(function($item) {
                        if (strpos($item->role, "admin")) {
                            if (!strpos($this->getUser()->getIdentity()->getData()['role'], 'admin')) {
                                return true;
                            }
                        }
                        return !$item->state;
                    })
                    ->setConfirm(function($item) {
                        return "Opravdu si přejete účet {$item->email} zablokovat?";
                    });

            $grid->addActionHref('unblock', 'Odblokovat')
                    ->setIcon('ok')
                    ->setDisable(function($item) {
                        if (strpos($item->role, "admin")) {
                            if (!strpos($this->getUser()->getIdentity()->getData()['role'], 'admin')) {
                                return true;
                            }
                        }

                        return $item->state;
                    })
                    ->setConfirm(function($item) {
                        return "Opravdu si přejete účet {$item->email} odblokovat?";
                    });


            $grid->setExport();
        }
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
        $this->redirect('User:edit', $id);
    }

    public function actionReset($id) {
        if ($this->authenticator->isAdminRow($id, $this->getUser()->getIdentity()->getData()['role'])) {
            $this->flashMessage('Nemáte právo na změnu uživatele admin.', 'error');
            $this->redirect('default');
        } else {
            $res = $this->authenticator->resetUserPassword($id);
            if ($res === 0) {
                $this->flashMessage('Chyba při ukládání vygenerovaného hesla. ', 'error');
            } else {
                $message = new Message;
                $email = $this->authenticator->get($id)->email;
                $message->addTo($email)
                        ->setFrom('office@amalteia.cz');
                $template = $this->createTemplate();
                $template->setFile(__DIR__ . '/templates/User/resetemail.latte');
                $template->newPassword = $res;
                $message->setHtmlBody($template);
                $mailer = new SendmailMailer;
                $mailer->send($message);
                $this->flashMessage('Heslo bylo resetováno. Uživateli byl odeslán email.', 'success');
            }
            $this->redirect('default');
        }
    }
    
    public function actionData($id) {
//$this->flashMessage("Akce '$this->action' pro řádek s id: $id byla provedena.", 'success');
        if ($this->authenticator->isAdminRow($id, $this->getUser()->getIdentity()->getData()['role'])) {
            $this->flashMessage('Nemáte právo na změnu uživatele admin.', 'error');
            $this->redirect('default');
        } else {
            $this->flashMessage('Akce nebyla doposud implementována.', 'info');
            $this->redirect('default');
        }
    }

    public function actionDelete() {
        /*
        $id = $this->getParameter('id');
        $id = is_array($id) ? implode(', ', $id) : $id;
        if ($this->authenticator->isAdminRow($id, $this->getUser()->getIdentity()->getData()['role'])) {
            $this->flashMessage('Nemáte právo na změnu uživatele admin.', 'error');
            $this->redirect('default');
        } else {
            $this->authenticator->delete($id);
            $this->flashMessage("Akce '$this->action' pro řádek s id: $id byla provedena.", 'success');
            $this->redirect('default');
        }*/
        $this->redirect('default');
    }

    public function actionBlock() {
        $this->changeState(0);
    }

    public function actionUnblock() {
        $this->changeState(1);
    }

    private function changeState($state) {
        $id = $this->getParameter('id');
        if ($this->authenticator->isAdminRow($id, $this->getUser()->getIdentity()->getData()['role'])) {
            $this->flashMessage('Nemáte právo na změnu uživatele admin.', 'error');
            $this->redirect('default');
        } else {
            $id = is_array($id) ? implode(', ', $id) : $id;
            $this->user->getAuthenticator()->setUserState($id, $state);
            $this->flashMessage("Akce blokace-odblokace pro řádek s id: $id byla provedena.", 'success');
            $this->redirect('default');
        }
    }

    protected function createComponentEditUserForm() {
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
        $form->addText(\App\Model\Authenticator::COLUMN_PHONE, Html::el('span')->setText($this->translator->translate("ui.signMessage.phone"))->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->setRequired($this->translator->translate("ui.signMessage.phoneMsg"))
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.phoneIncorect"), '^(\+420|\+421){1} {1}[1-9][0-9]{2} {1}[0-9]{3} {1}[0-9]{3}$');
        $form->addText(\App\Model\Authenticator::COLUMN_BIRTH_DATE, Html::el('span')->setText($this->translator->translate("ui.signMessage.birthDay")))
                ->setRequired(false)
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.birthDayIncorect"), '([0-9]\s*){1,2}\.([0-9]\s*){1,2}\.([0-9]\s*){4}')
                ->setHtmlId('datepicker1');

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));

         $form->addText(\App\Model\Authenticator::COLUMN_VIP_DATE, Html::el('span')->setText($this->translator->translate("ui.signMessage.vipDay")))
                ->setRequired(false)
                ->addRule(Form::PATTERN, $this->translator->translate("ui.signMessage.vipDayIncorect"), '([0-9]\s*){1,2}\.([0-9]\s*){1,2}\.([0-9]\s*){4}')
                ->setHtmlId('datepicker1');

        $roles = array(
            'user' => 'Uživatel',
            'partner' => 'Partner',
            'office' => 'Pracovník kanceláře',
        );

        if ($this->user->isInRole('admin')) {
            $roles = array(
                'user' => 'Uživatel',
                'partner' => 'Partner',
                'office' => 'Pracovník kanceláře',
                'admin' => 'Administrátor',
            );
        }

        $form->addCheckboxList(\App\Model\Authenticator::COLUMN_ROLE, 'Práva:', $roles)
                ->setAttribute('class', 'checkbox')
                ->addRule(Form::FILLED, 'Zvolena musí být alespoň jedna role');


        $state = array(
            '0' => 'Blokovaný',
            '1' => 'Aktivní',
        );

        $form->addRadioList(\App\Model\Authenticator::COLUMN_STATE, 'Stav:', $state)->setAttribute('class', 'radio')->addRule(Form::FILLED, 'Vyberte stav')->getSeparatorPrototype()->setName(NULL);



        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-12"));
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
        $id = $this->getParameter('id');

        $idPar = $values[\App\Model\Authenticator::COLUMN_PARTNER_ID];
        if($idPar > 936000000 || $idPar < 936000000)
        {
            $partnerID = $this->authenticator->get($idPar);
            if ($partnerID == null)
            {
                $form->addError($this->translator->translate("ui.signMessage.partnerNoExist"));
            }
        }

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
            $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE] = \DateTime::createFromFormat('d.m.yy', $values[\App\Model\Authenticator::COLUMN_BIRTH_DATE]); 
            $values[\App\Model\Authenticator::COLUMN_VIP_DATE] = \DateTime::createFromFormat('d.m.yy', $values[\App\Model\Authenticator::COLUMN_VIP_DATE]);   
            $id = $this->getParameter('id');
            $values[\App\Model\Authenticator::COLUMN_ROLE] = implode(",", $values[\App\Model\Authenticator::COLUMN_ROLE]);
            $this->authenticator->update($id, $values);
            $this->flashMessage($this->translator->translate("ui.signMessage.changeSaved"), 'success');
            $this->redirect('default');
    }
}