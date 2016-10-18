<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    App\Controls\Grido\MyGrid,
    Nette\Utils\Html;


class CategoryPresenter extends PrivatePresenter
{
     /** @inject @var \App\Model\Authenticator */
    public $authenticator;

    /** @inject @var \App\Model\Categories */
    public $categories;

    /** @var \Nette\Database\Context @inject */
    public $database;

    public $category = array('0' => 'Finanční zdraví', '1' => 'Tělesné zdraví', '2' => 'Psychické  zdraví');

    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->title = $this->translator->translate("ui.menuItems.categories");
    }

    public function renderEdit($id) {
        $category = $this->categories->get($id);
        if (!$category) {
             $this->template->Name = 'Vložení nové kategorie';
        } else 
        {
            $data = $category->toArray();
            $this->template->Name = 'Editace kategorie "'.$category->label.'"';
            $this['editCategoryForm']->setDefaults($data);
        }
    }

     protected function createComponentGrid($name) {
        $id = $this->getParameter('id');

        $grid = new MyGrid($this, $name);
        $grid->model = $this->database->table(\App\Model\Categories::TABLE_NAME);

        $grid->translator->setLang('cs');
        $grid->addColumnText(\App\Model\Categories::COLUMN_ID, 'ID')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();

        $grid->addColumnText(\App\Model\Categories::COLUMN_SUB_ID, 'Hlavní kategorie')
                        ->setSortable()
                        ->setReplacement($this->category)
                ->cellPrototype->class[] = 'center';
        $grid->addFilterSelect(\App\Model\Categories::COLUMN_SUB_ID, 'Hlavní kategorie', $this->category);

        $grid->addColumnText(\App\Model\Categories::COLUMN_LABEL, 'Název')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();

        $grid->addColumnText(\App\Model\Categories::COLUMN_ORD, 'Pořadí')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();
       

        $grid->filterRenderType = \Grido\Components\Filters\Filter::RENDER_INNER;

        $grid->addActionHref('goEdit', 'Edit');
          
        $grid->addActionHref('delete', 'Smazat');

        $grid->addActionHref('up', 'Nahoru');

        $grid->addActionHref('down', 'Dolu');
        
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
        $this->redirect('Category:edit', $id);
    }

    public function actionUp($id) {
        $category = $this->categories->get($id);
        $index1 = $category->ord;
        $index2 = $index1-1;
        $list = $this->categories->getList()->where('sub_id', $category->sub_id)->where('ord', $index2)->fetch();
        if($list != null)
        {
             $catRow = $category->toArray();
             $catRow['ord'] = $index2;
             $category->update($catRow);

             $catRow = $list->toArray();
             $catRow['ord'] = $index1;
             $list->update($catRow);
        }

        $this->redirect('default');
    }

    public function actionDown($id) {
        $category = $this->categories->get($id);
        $index1 = $category->ord;
        $index2 = $index1+1;
        $list = $this->categories->getList()->where('sub_id', $category->sub_id)->where('ord', $index2)->fetch();
        if($list != null)
        {
             $catRow = $category->toArray();
             $catRow['ord'] = $index2;
             $category->update($catRow);

             $catRow = $list->toArray();
             $catRow['ord'] = $index1;
             $list->update($catRow);
        }

        $this->redirect('default');
    }

    public function actionDelete() {
        $id = $this->getParameter('id');
        $this->categories->delete($id);
        $this->flashMessage("Akce '$this->action' pro řádek s id: $id byla provedena.", 'success');
        $this->redirect('default');
    }

    protected function createComponentEditCategoryForm() {
        $categoryId = $this->getParameter('id');
        $form = new Nette\Application\UI\Form;
        $form->addSelect(\App\Model\Categories::COLUMN_SUB_ID, Html::el('span')->setText('Hlavní kategorie:')->addHtml(Html::el('span')->class('form-required')->setHtml('*')), $this->category)
            ->setPrompt('Zvolte hlavní kategorii')
            ->addRule(Form::FILLED, 'Vyplňte hlavní kategorii');
        $form->addText(\App\Model\Categories::COLUMN_LABEL, Html::el('span')->setText('Název')->addHtml(Html::el('span')->class('form-required')->setHtml('*')))
                ->addRule(Form::FILLED, 'Vyplňte název kategorie'); 
  
        if ($categoryId) {
            $form->addSubmit('send', 'Uložit změny');
        } else {
            $form->addSubmit('send', 'Přidat kategorii');
        }

        $form['send']->getControlPrototype()->class('btn btn-success');
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = 'div';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('form-line');
        $renderer->wrappers['label']['container'] = NULL;
        $renderer->wrappers['control']['container'] = NULL;
        $form->addProtection($this->translator->translate("ui.signMessage.protectionMessage"));
        // call method signInFormSucceeded() on success
        $form->onSuccess[] = array($this, 'categoryFormSucceeded');      
        return $form;
    }

    public function categoryFormSucceeded($form, $values) {
        $categoryId = $this->getParameter('id');
        $values = $form->getValues(TRUE);

        if ($categoryId) {
            $this->categories->update($categoryId, $values);
            $this->flashMessage($this->translator->translate("ui.signMessage.changeSaved"), 'success');
        } else {
             $sub = $values['sub_id'];
            $list = $this->categories->getList($values)->where('sub_id', $sub);
            $max = 0;
            foreach ($list as $cat)
            {
                if($cat['ord'] >= $max)
                {
                    $max = $cat['ord'];
                }   
            }   
            $values['ord'] = $max+1;
            $categoryId = $this->categories->create($values);
            $this->flashMessage('Kategorie vložena', 'success');
           
        }   
       
        $this->redirect('default');  
    }
}