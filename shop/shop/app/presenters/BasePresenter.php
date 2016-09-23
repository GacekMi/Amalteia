<?php

namespace App\Presenters;

use Nette; 

abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @persistent */
    public $locale;

    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;

    // rest of your BasePresenter

    private function getMenu() {
       $html='';
       $html.='<a href="'.$this->link('Gallery:default').'">'.$this->translator->translate("ui.menuItems.gallery").'</a>';  
       $html.='<a href="'.$this->link('Page:default').'">'.$this->translator->translate("ui.menuItems.page").'</a>';
       $html.='<a href="'.$this->link('Dashboard:default').'">'.$this->translator->translate("ui.menuItems.dashboard").'</a>';
       $html.='<a href="'.$this->link('Profile:default').'">'.$this->translator->translate("ui.menuItems.profile").'</a>';
       $html.='<a href="'.$this->link('User:default').'">'.$this->translator->translate("ui.menuItems.user").'</a>';  
       if ($this->getUser()->isLoggedIn())
        {
            $html.='<li><a href="'.$this->link('Profile:default').'">'.$this->translator->translate("ui.menuItems.profile").'</a></li>';
            //if($this->user->isAllowed('Cvs','default'))
            $html.='<li><a href="'.$this->link('Dashboard:default').'">'.$this->translator->translate("ui.menuItems.dashboard").'</a></li>';
            $html.='<li><a href="'.$this->link('Sign:out').'">'.$this->translator->translate("ui.menuItems.logOut").'</a></li>';
        }
        else
        {
            $html.='<a href="'.$this->link('Sign:in').'">'.$this->translator->translate("ui.menuItems.logIn").'</a>';    
        }
        
        return $html;
    }

    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->menu = $this->getMenu();
    }
}