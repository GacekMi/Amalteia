<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Mail\Message,
    Nette\Mail\SendmailMailer,
    App\Controls\Grido\MyGrid,
    Nette\Utils\Html;


class BasketPresenter extends PrivatePresenter
{
    /** @inject @var \App\Model\Goods */
    public $goods;

    public function beforeRender()
    {
        parent::beforeRender();
        $this->template->title = "Košík";
    }

    public function renderDefault()
    {
        $session = $this->getSession('basket');
        $basketIsEmpty = true;
        if($session->itemsBasket != null)
        {
            $basketIsEmpty = false;
            $keys = array();
            foreach ($session->itemsBasket as $key => $item)
            {
               $keys[]= $key;
            }

            $goodsDB = $this->goods->getGooods($keys);
            $goods = [];
            $totalOrderPrice = 0;
            $orderCurrency = "";
            foreach ($goodsDB as $goodDB)
            {
                if($goodDB->state == 'Z')
                {
                    $good = $goodDB->toArray();
                    $good['count'] = $session->itemsBasket[$goodDB->id];
                    //cena dle prihlaseneho uzivatele
                    $isVIP = false;

                    if($this->getUser()->getIdentity()!=null && $this->getUser()->isLoggedIn())
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

                    $good['total_price'] = $good['price'] * $good['count'];
                    $totalOrderPrice += $good['total_price'];
                    $good['unit'] = $this->goods->unit[$goodDB->unit];
                    $good['currency'] = $this->goods->currency[$goodDB->currency];
                    $orderCurrency = $good['currency'];
                    $good['availability'] = $this->goods->availability[$goodDB->availability];
                    $goods[] = $good;
                }
            }
            $this->template->goods =  $goods;
            $this->template->totalOrderPrice =  $totalOrderPrice;
            $this->template->orderCurrency = $orderCurrency;
        }

        $this->template->basketIsEmpty = $basketIsEmpty;
    }

    public function renderPlusItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              $this->getSession('basket')->itemsBasket[$id]++;
        }

        $this->redirect('default');
    }

    public function renderMinusItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              $this->getSession('basket')->itemsBasket[$id]--;
              if($this->getSession('basket')->itemsBasket[$id]< 1)
              {
                   unset($this->getSession('basket')->itemsBasket[$id]);
              }
        }

        $this->redirect('default');
    }

    public function renderRemoveItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              unset($this->getSession('basket')->itemsBasket[$id]);
              $this->presenter->flashMessage('Zbozi bylo odebráno z kosiku.', 'success');
        }

        $this->redirect('default');
    }

    public function renderChangeCountItem($id)
    {
        if(isset($this->getSession('basket')->itemsBasket[$id]))
        {
              $count = $this->getParameter('count');
              if($count < 1)
              {
                unset($this->getSession('basket')->itemsBasket[$id]);
                $this->presenter->flashMessage('Zbozi bylo odebráno z kosiku.', 'success');
              }
              else{
                  $this->getSession('basket')->itemsBasket[$id] = $count;
                  $this->presenter->flashMessage('U zbozi byl upraven počet položek v kosiku.', 'success');
              }
        }
        $this->redirect('default');
    }

    public function renderDeleteBasket()
    {
        if($this->getSession('basket') != null)
        {
              unset($this->getSession('basket')->itemsBasket);
              $this->presenter->flashMessage('Kosik vymazán.', 'success');
        }

        $this->redirect('Gallery:default');
    }
}