<?php

namespace App\Presenters;

use App\Model,
    Nette;


class GalleryPresenter extends BasePresenter
{
    /** @inject @var \App\Model\Categories */
    public $categories;

    /** @inject @var \App\Model\Goods */
    public $goods;

    public function beforeRender()
    {
         parent::beforeRender();
         $this->template->title = $this->translator->translate("ui.menuItems.gallery");
         $this->fillCategory();
    }

     private function fillCategory()
    {
        $this->getMembership();
        $fin = array();
        $tel = array();
        $psy = array();

        $list = $this->categories->getList();
        foreach ($list as $cat)
        {
            switch ($cat->sub_id) {
                case 0:
                    $fin[$cat->id]=$cat->label;
                    break;
                case 1:
                    $tel[$cat->id]=$cat->label;
                    break;
                case 2:
                    $psy[$cat->id]=$cat->label;
                    break;
            }
        }

        $this->template->fin = $fin;
        $this->template->tel = $tel;
        $this->template->psy = $psy;
    }

     public function getMembership() {
      $goodsDB = $this->goods->getPreviewList(-1);
      $memberships = [];
      foreach ($goodsDB as $goodDB)
      {
          if($goodDB->state == 'Z')
          {
            $membership = $goodDB->toArray();
            $membership['id'] = $goodDB->id;
            $membership['image'] = $goodDB->image;
            $membership['label'] = $goodDB->label;
            $membership['price'] = $goodDB->price_vat;
            $membership['unit'] = $this->goods->unit[$goodDB->unit];
            $membership['currency'] = $this->goods->currency[$goodDB->currency];
            $memberships[] = $membership;
          }
      }
      $this->template->membership =  $memberships;
    }
}
