<?php

namespace App\Presenters;

use Nette,
    App\Model;

/**
 * Base presenter for all application presenters.
 */
abstract class PrivatePresenter extends BasePresenter {

    /** @inject @var \App\Model\Authenticator */
    public $authenticator;
    
    public function startup()
    {
        parent::startup();

        if ($this->name != 'Sign') {
            if (!$this->user->isLoggedIn()) {
                if ($this->user->getLogoutReason() === Nette\Security\User::INACTIVITY) {
                    $this->flashMessage($this->translator->translate("ui.signMessage.logOutNoActivity"), 'info');
                }
                
                if (!$this->user->isAllowed($this->name, $this->action)) {
                     $this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
                }

            } else {
                if (!$this->user->isAllowed($this->name, $this->action)) {
                    $this->flashMessage($this->translator->translate("ui.signMessage.accessDeniedPermi"), 'error');
                    $this->redirect('Gallery:');
                }
            }
        }
        
        if ($this->name == 'Sign' && $this->action == 'out' && !$this->user->isLoggedIn())
        {
            $this->redirect('Sign:In');
        }
    }
}