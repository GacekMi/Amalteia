<?php

namespace App\Model;

use Nette,
App\Model,
Nette\Utils\Strings;

class OrderItems extends Nette\Object{

	const
	TABLE_NAME = 'order_items',
	COLUMN_ID = 'id',
    COLUMN_ORDER_ID = 'order_id',
    COLUMN_TYPE = 'type',
    COLUMN_GOOD_ID = 'good_id',
    COLUMN_LABEL = 'label',
    COLUMN_PRICE = 'price',
    COLUMN_PRICE_VAT = 'price_vat',
    COLUMN_VAT = 'vat',
    COLUMN_CURRENCY = 'currency',
    COLUMN_UNIT = 'unit',
    COLUMN_QUANTITY= 'quantity';

	
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

    public function getByOrder($orderId){
         return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ORDER_ID, $orderId);
    }

	 public function delete($key) {
        return $this->database->table(self::TABLE_NAME)->where('id', $key)->delete();
    }

    public function getList() {
        return $this->database->table(self::TABLE_NAME);
    }

	public function create(array $values) {
        return $this->database->table(self::TABLE_NAME)->insert($values);
    }	
}