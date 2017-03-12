<?php

namespace App\Model;

use Nette,
App\Model,
Nette\Utils\Strings;

class Goods extends Nette\Object{

	const
	TABLE_NAME = 'goods',
	COLUMN_ID = 'id',
	COLUMN_LABEL = 'label',
	COLUMN_SHORT_DESCRIPTION = 'short_description',
	COLUMN_DESCRIPTION = 'description',
	COLUMN_IMAGE = 'image',
	COLUMN_PRICE = 'price',
	COLUMN_PRICE_VAT = 'price_vat',
	COLUMN_UNIT = 'unit',
	COLUMN_FLAG = 'flag',
	COLUMN_STATE = 'state',
	COLUMN_TRANSPORT = 'transport',
	COLUMN_AVAILABILITY = 'availability',
	COLUMN_DISCOUNT_TYPE = 'discount_type',
	COLUMN_DISCOUNT = 'discount',
	COLUMN_VAT = 'vat',
	COLUMN_STOCK = 'stock',
	COLUMN_CURRENCY = 'currency',
	COLUMN_D_PRICE = 'd_price',
	COLUMN_D_PRICE_VAT = 'd_price_vat',
	COLUMN_WEIGHT = 'weight',
	COLUMN_CATEGORY = 'category',
	COLUMN_DOCUMENTS = 'documents',
	COLUMN_COMPARSION = 'comparsion',
	COLUMN_TIPS = 'tips';
	
	/** @var Nette\Database\Context */
    private $database;

    /** @var \Kdyby\Translation\Translator  */
    public $translator;

	/** @inject @var \App\Model\Categories */
    public $categories;

	public $state = array('Z' => 'Zobrazeno','N' => 'Nezobrazeno','S' => 'Staženo');
    public $availability = array('S' => 'Skladem','C' => 'Do 14 dnů','D' => 'U dodavatele','X' => 'Nedostupné','N' => 'Na dotaz', 'Z' => 'Do vyprodání zásob');
    public $discontType = array('0' => 'Procenta', '1' => 'Pevná částka', '2' => 'Není');
    public $unit = array('0' => 'ks', '1' => 'balení');
    public $flag = array('0'=> 'Nic', 'N' => 'Novinka', 'S' => 'Sleva', 'A' => 'Akce', 'P' => 'Poslední kus');
    public $transport = array('0' => 'Obyčejná', '1' => 'Nadrozměrná', '2' => 'Zdarma');
    public $vat = array('0' => '0%', '1' => '15%', '2' => '21%', '3' => '10%');
    public $currency = array('0' => 'Kč', '1' => 'EUR');

	public function fillGridCategory()
    {
        $category = array();
        $list = $this->categories->getList();
        foreach ($list as $cat)
        {
             $category[$cat->id]=$cat->label;
        }

        return $category;
    }

    public function fillCategory()
    {
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

        $category = [
            'Finanční zdraví' => $fin,
            'Tělesné zdraví' => $tel,
            'Psychické  zdraví' => $psy,
            '-1' => 'Klubové Balíčky',
        ];

        return $category;
    }

    public function __construct(Nette\Database\Context $database, Nette\Localization\ITranslator $translator, Categories $categories) {
        $this->database = $database;
        $this->translator = $translator;
		$this->categories = $categories;
    }

	public function update($key, $values) {
         return $this->database->table(self::TABLE_NAME)->where('id', $key)->update($values);
    }
    
    public function get($key) {
        return $this->database->table(self::TABLE_NAME)->get($key);
    }

    public function getGooods($keys) {
         return $this->database->table(self::TABLE_NAME)->where('id', $keys);
    }

	 public function delete($key) {
        return $this->database->table(self::TABLE_NAME)->where('id', $key)->delete();
    }

    public function getList() {
        return $this->database->table(self::TABLE_NAME);
    }

	public function getPreviewList($category){
		$columns = array(self::COLUMN_ID,self::COLUMN_ID,self::COLUMN_LABEL,self::COLUMN_IMAGE, self::COLUMN_PRICE_VAT, self::COLUMN_CURRENCY, self::COLUMN_UNIT,  self::COLUMN_AVAILABILITY, self::COLUMN_STATE, self::COLUMN_D_PRICE_VAT);
		$columns = implode(",", $columns);
		return $this->database->table(self::TABLE_NAME)->Select($columns)->where('category', $category);
	}

	public function create(array $values) {
        return $this->database->table(self::TABLE_NAME)->insert($values);
    }	
}