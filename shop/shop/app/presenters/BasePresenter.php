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
}