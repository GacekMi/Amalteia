<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    App\Controls\Grido\MyGrid,
    Nette\Utils\Html,
    Nette\Application\UI\Multiplier;


class GoodPresenter extends PrivatePresenter
{
     /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    /** @inject @var \App\Model\Goods */
    public $goods;

    /** @var \Nette\Database\Context @inject */
    public $database;

    /** @inject @var \App\Model\Categories */
    public $categories;

    private $category;
    private $gridCategory;

    protected function createComponentAddItemsForm()
    {
        return new Multiplier(function ($itemId) {
            $form = new Nette\Application\UI\Form;
            $form->addText('count', '')
                ->addRule($form::FILLED)
                ->addRule($form::INTEGER)
                ->setDefaultValue(1)
                ->addRule(Form::MIN, 'Počet kusů musí být nezáporný a větší než nula.', 1)
                ->setAttribute('class', 'good-detail-count');
            $form->addHidden('itemId', $itemId);
            
            $form->addSubmit('send', 'Přidat do košíku')
                ->setAttribute('class', 'btn button-gallery-cart ');
          
            $form->onSuccess[] = array($this, 'addItemsFormSucceeded');
            $renderer = $form->getRenderer();
            $renderer->wrappers['controls']['container'] = Html::el('p')->class('count-cart-button-line');
            $renderer->wrappers['pair']['container'] = Html::el('span');
            $renderer->wrappers['label']['container'] = NULL;
            $renderer->wrappers['control']['container'] = NULL;
            return $form;
        });
    }


    public function addItemsFormSucceeded($form) {
        $values = $form->getValues();
        try {
            $session = $this->getSession('basket');
            if(isset($session->itemsBasket[$values->itemId]))
            {
                  $session->itemsBasket[$values->itemId]+= $values->count;
            }
            else{
                  $session->itemsBasket[$values->itemId] = $values->count;
            }

            $this->presenter->flashMessage('Zbozi vlozeno do kosiku.'.$values->itemId, 'success');
        } catch (\Exception $exc) {
            $this->presenter->flashMessage($exc->getMessage(), 'danger');
        }
    }

    protected function createComponentAddItemForm()
    {
        return new Multiplier(function ($itemId) {
            $form = new Nette\Application\UI\Form;
            $form->addHidden('itemId', $itemId);
            $form->addSubmit('send', 'Přidat do košíku')
                ->setAttribute('class', 'btn button-gallery-cart');
            $form->onSuccess[] = array($this, 'addItemFormSucceeded');
            return $form;
        });
    }

    public function addItemFormSucceeded($form) {
        $values = $form->getValues();
        try {
            $session = $this->getSession('basket');
            if(isset($session->itemsBasket[$values->itemId]))
            {
                  $session->itemsBasket[$values->itemId]+= 1;
            }
            else{
                  $session->itemsBasket[$values->itemId] = 1;
            }

            $this->presenter->flashMessage('Zbozi vlozeno do kosiku.'.$values->itemId, 'success');
        } catch (\Exception $exc) {
            $this->presenter->flashMessage($exc->getMessage(), 'danger');
        }
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->title = $this->translator->translate("ui.menuItems.good");
    }

    public function renderDefault($id) {
      $goodsDB = $this->goods->getPreviewList($id);
      $goods = [];
      foreach ($goodsDB as $goodDB)
      {
          if($goodDB->state == 'Z')
          {
            $good = $goodDB->toArray();

            $good['id'] = $goodDB->id;
            $good['image'] = $goodDB->image;
            $good['label'] = $goodDB->label;
            //cena dle prihlaseneho uzivatele
            $isVIP = false;

            if($this->getUser()->getIdentity()!=null)
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
            $good['unit'] = $this->goods->unit[$goodDB->unit];
            $good['currency'] = $this->goods->currency[$goodDB->currency];
            $good['availability'] = $this->goods->availability[$goodDB->availability];
            $goods[] = $good;
          }
      }
      $this->template->goods =  $goods;
    }

    public function renderDetail($id) {
           $goodDB = $this->goods->get($id);
           //kontrola state 
            if($goodDB->state != 'Z')
            {
                $this->redirect('default');
            }
            else
            {
                $good = $goodDB->toArray();
                $good['id'] = $goodDB->id;
                $good['image'] = $goodDB->image;
                $good['label'] = $goodDB->label;
               //cena dle prihlaseneho uzivatele
               $isVIP = false;

                if($this->getUser()->getIdentity()!=null)
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
                $good['unit'] = $this->goods->unit[$goodDB->unit];
                $good['currency'] = $this->goods->currency[$goodDB->currency];
                $good['availability'] = $this->goods->availability[$goodDB->availability];

                $good['short_description'] = $goodDB->short_description;
                $good['description'] = $goodDB->description;

                $good['vat'] = $this->goods->vat[$goodDB->vat];
                $good['flag'] = $this->goods->flag[$goodDB->flag];
                $good['transport'] = $this->goods->transport[$goodDB->transport];

                //dopsat moznost slevy pro prijlaseny atd....
                $this->template->good =  $good;
                $this->template->id =  $good['id'];
            }
    }

    public function renderEdit($id) {
        $good = $this->goods->get($id);
        $this->template->isImage = False;
        if (!$good) {
             $this->template->Name = 'Vložení nového zboží';
        } else 
        {
            $data = $good->toArray();
            $this->template->Name = 'Editace zboží "'.$good->label.'"';
            if(strlen($data[\App\Model\Goods::COLUMN_IMAGE])>0)
            {
                $this->template->isImage = True;
                $this->template->image = $data[\App\Model\Goods::COLUMN_IMAGE];
            }
            
            $this['editGoodForm']->setDefaults($data);
        }
    }

    protected function createComponentGrid($name) {
        $this->category = $this->goods->fillGridCategory();
        $id = $this->getParameter('id');

        $grid = new MyGrid($this, $name);
        $grid->model = $this->database->table(\App\Model\Goods::TABLE_NAME);

        $grid->translator->setLang('cs');
        $grid->addColumnText(\App\Model\Goods::COLUMN_ID, 'ID')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Goods::COLUMN_LABEL, 'Název')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Goods::COLUMN_PRICE, 'Cena')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Goods::COLUMN_PRICE_VAT, 'Cena s DPH')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Goods::COLUMN_D_PRICE_VAT, 'K cena s DPH')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
        $grid->addColumnText(\App\Model\Goods::COLUMN_DISCOUNT, 'Sleva')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();

         $grid->addColumnText(\App\Model\Goods::COLUMN_CATEGORY, 'Kategorie')
                        ->setSortable()
                        ->setReplacement($this->category)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_CATEGORY, 'Kategorie', $this->category);

        $grid->addColumnText(\App\Model\Goods::COLUMN_CURRENCY, 'Měna')
                        ->setSortable()
                        ->setReplacement($this->goods->currency)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_CURRENCY, 'Měna', $this->goods->currency);
        $grid->addColumnText(\App\Model\Goods::COLUMN_DISCOUNT_TYPE, 'Typ slevy')
                        ->setSortable()
                        ->setReplacement($this->goods->discontType)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_DISCOUNT_TYPE, 'Typ slevy', $this->goods->discontType);

        $grid->addColumnText(\App\Model\Goods::COLUMN_UNIT, 'Jednotka')
                        ->setSortable()
                        ->setReplacement($this->goods->unit)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_UNIT, 'Jednotka', $this->goods->unit);

        $grid->addColumnText(\App\Model\Goods::COLUMN_AVAILABILITY, 'Dostupnost')
                        ->setSortable()
                        ->setReplacement($this->goods->availability)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_AVAILABILITY, 'Dostupnost', $this->goods->availability);

        $grid->addColumnText(\App\Model\Goods::COLUMN_STOCK, 'Skald')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();

        $grid->addColumnText(\App\Model\Goods::COLUMN_TRANSPORT, 'Doprava')
                        ->setSortable()
                        ->setReplacement($this->goods->transport)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_TRANSPORT, 'Doprava', $this->goods->transport);

        $grid->addColumnText(\App\Model\Goods::COLUMN_STATE, 'Stav')
                        ->setSortable()
                        ->setReplacement($this->goods->state)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_STATE, 'Stav', $this->goods->state);

        $grid->addColumnText(\App\Model\Goods::COLUMN_VAT, 'DPH')
                        ->setSortable()
                        ->setReplacement($this->goods->vat)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_VAT, 'DPH',$this->goods->vat );

        $grid->addColumnText(\App\Model\Goods::COLUMN_FLAG, 'Flag')
                        ->setSortable()
                        ->setReplacement($this->goods->flag)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Goods::COLUMN_FLAG, 'Flag', $this->goods->flag);
       

        $grid->filterRenderType = \Grido\Components\Filters\Filter::RENDER_INNER;

        $grid->addActionHref('goEdit', 'Edit');
          
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
        $this->redirect('Good:edit', $id);
    }

    public function actionDelete() {
        $id = $this->getParameter('id');
        $good = $this->goods->get($id);
        $data = $good->toArray();
        if(strlen($data[\App\Model\Goods::COLUMN_IMAGE])>0)
        {
                //smazani
                unlink(IMG_DIR . '/images/goods/'. $data[\App\Model\Goods::COLUMN_IMAGE]);
                unlink(IMG_DIR . '/images/goods/thumbs/'. $data[\App\Model\Goods::COLUMN_IMAGE]);
        }
        $this->goods->delete($id);
        $this->flashMessage("Akce '$this->action' pro řádek s id: $id byla provedena.", 'success');
        $this->redirect('list');
    }

    protected function createComponentEditGoodForm() {
        $this->category = $this->goods->fillCategory();
        $goodId = $this->getParameter('id');
        $form = new Nette\Application\UI\Form;

        $form->addHidden(\App\Model\Goods::COLUMN_IMAGE);

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));
        $form->addText(\App\Model\Goods::COLUMN_LABEL, Html::el('span')->setText('Název zboží')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte název zboží'); 

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));
        
        $form->addUpload('upload_image', Html::el('span')->setText('Obrázek zboží:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
            ->addRule(Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 5 MB.', 5000 * 1024 /* v bytech */)
            ->setRequired(false)
            ->addRule(Form::IMAGE, 'Obrázek musí být JPEG, PNG.');

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-12"));
        $form->addTextArea(\App\Model\Goods::COLUMN_SHORT_DESCRIPTION, Html::el('span')->setText('Krátký popis')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte krátký popis'); 

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));
        $form->addText(\App\Model\Goods::COLUMN_PRICE, Html::el('span')->setText('Cena bez DPH')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte cenu bez DPH'); 
        $form->addText(\App\Model\Goods::COLUMN_D_PRICE, Html::el('span')->setText('Klubová cena')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte klubovou cenu bez DPH');
        $form->addSelect(\App\Model\Goods::COLUMN_VAT, Html::el('span')->setText('DPH:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->vat)
            ->setPrompt('Zvolte DPH')
            ->addRule(Form::FILLED, 'Vyplňte DPH');
        $form->addText(\App\Model\Goods::COLUMN_DISCOUNT, Html::el('span')->setText('Sleva')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte slevu'); 
        $form->addSelect(\App\Model\Goods::COLUMN_AVAILABILITY, Html::el('span')->setText('Dostupnost:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->availability)
            ->setPrompt('Zvolte dostupnost')
            ->addRule(Form::FILLED, 'Vyplňte dostupnost');
        $form->addSelect(\App\Model\Goods::COLUMN_TRANSPORT, Html::el('span')->setText('Doprava:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->transport)
            ->setPrompt('Zvolte dopravu')
            ->addRule(Form::FILLED, 'Vyplňte dopravu');
        $form->addSelect(\App\Model\Goods::COLUMN_UNIT, Html::el('span')->setText('Jednotka:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->unit)
            ->setPrompt('Zvolte jednotku')
            ->addRule(Form::FILLED, 'Vyplňte jednotku');
         $form->addSelect(\App\Model\Goods::COLUMN_CATEGORY, Html::el('span')->setText('Kategorie:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->category)
            ->setPrompt('Zvolte kategorii')
            ->addRule(Form::FILLED, 'Vyplňte kategorii');


        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-6"));
        $form->addText(\App\Model\Goods::COLUMN_PRICE_VAT, Html::el('span')->setText('Cena s DPH')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte cenu s DPH'); 
        $form->addText(\App\Model\Goods::COLUMN_D_PRICE_VAT, Html::el('span')->setText('Klubová cena s DPH')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte klubovou cenu s DPH'); 
        $form->addSelect(\App\Model\Goods::COLUMN_CURRENCY, Html::el('span')->setText('Měna:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->currency)
            ->setPrompt('Zvolte měnu')
            ->addRule(Form::FILLED, 'Vyplňte měnu');
        $form->addSelect(\App\Model\Goods::COLUMN_DISCOUNT_TYPE, Html::el('span')->setText('Druh slevy:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->discontType)
            ->setPrompt('Zvolte druh slevy')
            ->addRule(Form::FILLED, 'Vyplňte druh slevy');
        $form->addText(\App\Model\Goods::COLUMN_STOCK, Html::el('span')->setText('Stav na skladě:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte stav skladu'); 
        $form->addSelect(\App\Model\Goods::COLUMN_STATE, Html::el('span')->setText('Stav:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->state)
            ->setPrompt('Zvolte stav')
            ->addRule(Form::FILLED, 'Vyplňte stav');
        $form->addSelect(\App\Model\Goods::COLUMN_FLAG, Html::el('span')->setText('Flag:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->goods->flag)
            ->setPrompt('Zvolte flag')
            ->addRule(Form::FILLED, 'Vyplňte flag');
        $form->addText(\App\Model\Goods::COLUMN_WEIGHT, Html::el('span')->setText('Hmotnost v kg:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte hmotnost v kg'); 

        $form->addGroup()->setOption('container', Html::el('div')->class("col-lg-12"));
        $form->addTextArea(\App\Model\Goods::COLUMN_DESCRIPTION, Html::el('span')->setText('Popis')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte popis'); 
        $form->addTextArea(\App\Model\Goods::COLUMN_DOCUMENTS, 'Documenty');
        $form->addTextArea(\App\Model\Goods::COLUMN_COMPARSION, 'Porovnání');
        $form->addTextArea(\App\Model\Goods::COLUMN_TIPS, 'Tipy');
        if ($goodId) {
            $form->addSubmit('send', 'Uložit změny');
        } else {
            $form->addSubmit('send', 'Přidat zboží');
        }

        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        $form->addProtection($this->translator->translate("ui.signMessage.protectionMessage"));
        // call method signInFormSucceeded() on success
        $form->onSuccess[] = array($this, 'goodFormSucceeded');
        $form->onValidate[] = [$this, 'validateGoodForm'];        
        return $form;
    }

    public function validateGoodForm($form)
    {
        $values = $form->getValues();
        $file = $values['upload_image'];

        if(strlen($values[\App\Model\Goods::COLUMN_IMAGE])==0)
        {
            if (!$file->isImage() || !$file->isOk())
            {
                $form->addError('Zboží musí mít vybrán obrázek');
            }
        }
    }

     public function goodFormSucceeded($form, $values) {
        $goodId = $this->getParameter('id');
        $values = $form->getValues(TRUE);
        $values[\App\Model\Goods::COLUMN_IMAGE] = $this->saveGoodPhoto($values);
        unset($values['upload_image']);

        if ($goodId) {
            $id = $this->getParameter('id');

            $this->goods->update($id, $values);
            $this->flashMessage($this->translator->translate("ui.signMessage.changeSaved"), 'success');
            $this->redirect('list');
        } else {
            $this->goods->create($values);
            $this->flashMessage('Zboží vloženo', 'success');
            $this->redirect('list');
        }     
    }

    public function saveGoodPhoto($values){
        $file = $values['upload_image'];
        // kontrola jestli se jedná o obrázek a jestli se nahrál dobře
        if ($file->isImage() && $file->isOk()) {
            //Pripadne mazani
            if(strlen($values[\App\Model\Goods::COLUMN_IMAGE])>0)
            {
                //smazani
                unlink(IMG_DIR . '/images/goods/'. $values[\App\Model\Goods::COLUMN_IMAGE]);
                unlink(IMG_DIR . '/images/goods/thumbs/'. $values[\App\Model\Goods::COLUMN_IMAGE]);
            }

            // oddělení přípony pro účel změnit název souboru na co chceš se zachováním přípony
            $file_ext=strtolower(mb_substr($file->getSanitizedName(), strrpos($file->getSanitizedName(), ".")));
            // vygenerování náhodného řetězce znaků, můžeš použít i \Nette\Strings::random()
            $file_name = uniqid(rand(0,20), TRUE).$file_ext;
            // přesunutí souboru z temp složky někam, kam nahráváš soubory
            $file->move(IMG_DIR . '/images/goods/'. $file_name);

            //v případě, že chceš vytvořit z obrázku i miniaturu
            $image = \Nette\Image::fromFile(IMG_DIR . '/images/goods/'. $file_name);
            if($image->getWidth() > $image->getHeight()) {
            $image->resize(200, NULL);
            }
            else {
            $image->resize(NULL, 200);
            }
            $image->sharpen();
            $image->save(IMG_DIR . '/images/goods/thumbs/'. $file_name);

            //Ulozeni obr pro detail zbozi
            $image = \Nette\Image::fromFile(IMG_DIR . '/images/goods/'. $file_name);
            if($image->getWidth() > $image->getHeight()) {
            $image->resize(500, NULL);
            }
            else {
            $image->resize(NULL, 500);
            }
            $image->sharpen();
            $image->save(IMG_DIR . '/images/goods/detail/'. $file_name);

            //Ulozeni miniatury pro kosik nahled
            $image = \Nette\Image::fromFile(IMG_DIR . '/images/goods/'. $file_name);
            if($image->getWidth() > $image->getHeight()) {
            $image->resize(50, NULL);
            }
            else {
            $image->resize(NULL, 50);
            }
            $image->sharpen();
            $image->save(IMG_DIR . '/images/goods/cart/'. $file_name);

            return $file_name;
        }
        else
        {
            return $values[\App\Model\Goods::COLUMN_IMAGE];
        }
    }
}