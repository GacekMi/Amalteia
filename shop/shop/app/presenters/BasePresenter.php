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

    private function getLeftMenu() {
       $html='';
       $html.= '<li '.$this->isActive("Gallery").'><a href="'.$this->link('Gallery:default').'">'.$this->translator->translate("ui.menuItems.gallery").'</a></li>';
       $html.= '<li class="dropdown '.$this->isActivePages("Page").'">';
					$html.= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">'. $this->translator->translate("ui.menuItems.page") .' <span class="caret"></span></a>';
					$html.= '<ul class="dropdown-menu">';
                        $html.= '<li class="dropdown-header">'.$this->translator->translate("ui.footer.purchase").'</li>';
						$html.= '<li '.$this->isActiveSub("Page", "transport").'><a href="'.$this->link('Page:transport').'">'.$this->translator->translate("ui.footer.purchaseLink1").'</a></li>';
                        $html.= '<li '.$this->isActiveSub("Page", "payment").'><a href="'.$this->link('Page:payment').'">'.$this->translator->translate("ui.footer.purchaseLink2").'</a></li>';
                        $html.= '<li '.$this->isActive("").'><a href="https://amalteia.cz/docs/obchodni_podminky.pdf">'.$this->translator->translate("ui.footer.purchaseLink3").'</a></li>';
                         $html.= '<li '.$this->isActiveSub("Page", "benefits").'><a href="'.$this->link('Page:benefits').'">'.$this->translator->translate("ui.footer.purchaseLink6").'</a></li>';
                        //$html.= '<li '.$this->isActiveSub("Page", "rescission").'><a href="'.$this->link('Page:rescission').'">'.$this->translator->translate("ui.footer.purchaseLink4").'</a></li>';
                        //$html.= '<li '.$this->isActiveSub("Page", "services").'><a href="'.$this->link('Page:services').'">'.$this->translator->translate("ui.footer.purchaseLink5").'</a></li>';
						$html.= '<li role="separator" class="divider"></li>';
                        $html.= '<li class="dropdown-header">'.$this->translator->translate("ui.footer.order").'</li>';
						$html.= '<li '.$this->isActiveSub("Page", "state").'><a href="'.$this->link('Page:state').'">'.$this->translator->translate("ui.footer.orderLink1").'</a></li>';
                        $html.= '<li '.$this->isActiveSub("Page", "track").'><a href="'.$this->link('Page:track').'">'.$this->translator->translate("ui.footer.orderLink2").'</a></li>';
                        //$html.= '<li '.$this->isActiveSub("Page", "reclamation").'><a href="'.$this->link('Page:reclamation').'">'.$this->translator->translate("ui.footer.orderLink3").'</a></li>';
                        $html.= '<li '.$this->isActive("").'><a href="https://amalteia.cz/faq">'.$this->translator->translate("ui.footer.orderLink4").'</a></li>';
                        $html.= '<li role="separator" class="divider"></li>';
                        $html.= '<li class="dropdown-header">'.$this->translator->translate("ui.footer.aboutUs").'</li>';
						$html.= '<li '.$this->isActive("").'><a href="https://amalteia.cz">'.$this->translator->translate("ui.footer.aboutUsLink1").'</a></li>';
                        $html.= '<li '.$this->isActive("").'><a href="https://amalteia.cz/partner">'.$this->translator->translate("ui.footer.aboutUsLink2").'</a></li>';
                        $html.= '<li '.$this->isActive("").'><a href="https://amalteia.cz/contact">'.$this->translator->translate("ui.footer.aboutUsLink3").'</a></li>';
					$html.= '</ul>';
				$html.= '</li>';

        
        return $html;
    }

    private function getRightMenu(){
        $html='';
        if ($this->getUser()->isLoggedIn())
        {
                $html.= '<li class="dropdown">';
					$html.= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">'. $this->getUser()->getIdentity()->email .' <span class="caret"></span></a>';
					$html.= '<ul class="dropdown-menu">';
						$html.= '<li '.$this->isActive("Dashboard").'><a href="'.$this->link('Dashboard:default').'">'.$this->translator->translate("ui.menuItems.dashboard").'</a></li>';
                        $html.= '<li '.$this->isActive("Profile").'><a href="'.$this->link('Profile:default').'">'.$this->translator->translate("ui.menuItems.profile").'</a></li>';
                        $html.= '<li '.$this->isActive("User").'><a href="'.$this->link('User:default').'">'.$this->translator->translate("ui.menuItems.user").'</a></li>';
						$html.= '<li role="separator" class="divider"></li>';
						$html.= '<li '.$this->isActive("Sign").'><a href="'.$this->link('Sign:out').'">'.$this->translator->translate("ui.menuItems.logOut").'</a></li>';
					$html.= '</ul>';
				$html.= '</li>';
        }
        else
        {    
             $html.= '<li '.$this->isActive("Sign").'><a href="'.$this->link('Sign:in').'">'.$this->translator->translate("ui.menuItems.logIn").'</a></li>';
        }
                return $html;
    }

    public function beforeRender()
    {
        parent::beforeRender();
        
        $this->template->left_menu = $this->getLeftMenu();
        $this->template->right_menu = $this->getRightMenu();
        $this->template->lang = $this->translator->getLocale();
    }

     public function isActive($presenter)
     {
        if($this->name == $presenter)
        {
            return 'class="active"';
        }
    }

    public function isActivePages($presenter){
        if($this->name == $presenter)
        {
            return ' active';
        }
    }

    public function isActiveSub($presenter, $action)
     {
        if($this->name == $presenter && $this->action == $action)
        {
            return 'class="active"';
        }
    }
}