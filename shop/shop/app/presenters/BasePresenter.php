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
       $html.='<a href="'.$this->link('Gallery:default').'">Galerie</a>';  
       $html.='<a href="'.$this->link('Page:default').'">Nějaká stránka</a>';
       $html.='<a href="'.$this->link('Dashboard:default').'">*Dashboard</a>';
       $html.='<a href="'.$this->link('Profile:default').'">*Profile</a>';
       $html.='<a href="'.$this->link('User:default').'">*User</a>';  
       if ($this->getUser()->isLoggedIn())
        {
            $html.='<li><a href="'.$this->link('Profile:default').'">Profil</a></li>';
            //if($this->user->isAllowed('Cvs','default'))
            $html.='<li><a href="'.$this->link('Dashboard:default').'">Dashboard</a></li>';
            $html.='<li><a href="'.$this->link('Sign:out').'">Odhlásit</a></li>';
        }
        else
        {
            $html.='<a href="'.$this->link('Sign:in').'">Přihlásit</a>';    
        }
        
        return $html;
    }

    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->menu = $this->getMenu();
    }
}