<?php

namespace App\Presenters;

use Nette; 

abstract class BasePresenter extends Nette\Application\UI\Presenter
{
  private function getMenu() {
       //class="active" 
       $html='';
       $html.= '<li '.$this->isActive("Aboutus").'><a href="'.$this->link('Aboutus:default').'">O nás</a></li>';
       $html.= '<li '.$this->isActive("Partner").'><a href="'.$this->link('Partner:default').'">Klubové výhody</a></li>';
       $html.= '<li '.$this->isActive("Contact").'><a href="'.$this->link('Contact:default').'">Kontakt</a></li>';
       $html.= '<li '.$this->isActive("Faq").'><a href="'.$this->link('Faq:default').'">FAQ</a></li>';
       $html.= '<li class="dropdown">';
       $html.= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dokumenty <span class="caret"></span></a>';
       $html.= '<ul class="dropdown-menu">';
       $html.= '<li><a href="'.$this->template->basePath.'/docs/vypis_z_OR.pdf">Výpis z OR</a></li>';     
      // $html.= '<li '.$this->isActive("Private").'><a href="'.$this->link('Private:default').'">Ochrana soukromí</a></li>';//je to v op
       $html.= '<li><a href="'.$this->template->basePath.'/docs/obchodni_podminky.pdf">Obchodní podmínky</a></li>';
       $html.= '<li role="separator" class="divider"></li>';
       $html.= '<li class="dropdown-header">Amalteia klub</li>';
       $html.= '<li><a href="'.$this->template->basePath.'/docs/amalteia_klub_VOP.pdf">Amalteia klub VOP</a></li>';
       $html.= '<li><a href="'.$this->template->basePath.'/docs/amalteia_klub_specifikace_benefitu.pdf">Amalteia klub benefity</a></li>'; 

        
        return $html;
    }

    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->menu = $this->getMenu();
    }

    public function isActive($presenter)
    {
        if($this->name == $presenter)
        {
            return 'class="active"';
        }
    }
}