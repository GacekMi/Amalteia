<?php

namespace App\Model;

use Nette,
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
	COLUMN_D_PRICE_VAT = 'd_price_vat';
	
	/** @var Nette\Database\Context */
    private $database;

    /** @var \Kdyby\Translation\Translator  */
    public $translator;

    public function __construct(Nette\Database\Context $database, Nette\Localization\ITranslator $translator) {
        $this->database = $database;
        $this->translator = $translator;
    }

	public function update($key, $values) {
         return $this->database->table(self::TABLE_NAME)->where('id', $key)->update($values);
    }
    
    public function get($key) {
        return $this->database->table(self::TABLE_NAME)->get($key);
    }

	 public function delete($key) {
        return $this->database->table(self::TABLE_NAME)->where('id', $key)->delete();
    }

    public function getList() {
        return $this->database->table(self::TABLE_NAME);
    }

	public function getPreviewList(){
		$columns = array(self::COLUMN_ID,self::COLUMN_ID,self::COLUMN_SHORT_DESCRIPTION,self::COLUMN_IMAGE, self::COLUMN_PRICE_VAT);
		$columns = implode(",", $columns);
		return $this->database->table(self::TABLE_NAME)->Select($columns);
	}

	public function create(array $values) {
        return $this->database->table(self::TABLE_NAME)->insert($values);
    }	
}