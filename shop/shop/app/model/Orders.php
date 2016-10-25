<?php

namespace App\Model;

use Nette,
App\Model,
Nette\Utils\Strings;

class Orders extends Nette\Object{

	const
	TABLE_NAME = 'orders',
	COLUMN_ID = 'id',
    COLUMN_FIRST_NAME = 'first_name',
	COLUMN_LAST_NAME = 'last_name',
	COLUMN_EMAIL = 'email',
	COLUMN_PHONE = 'phone',
    COLUMN_DELIVERY_LABEL = 'delivery_label',
    COLUMN_DELIVERY_ADD = 'delivery_add',
    COLUMN_STREET = 'street',
	COLUMN_TOWN = 'town',
	COLUMN_PSC = 'psc',
    COLUMN_COUNTRY = 'country',
    COLUMN_INVOICE_LABEL = 'invoice_label',
    COLUMN_INVOICE_ICO = 'invoice_ico',
    COLUMN_INVOICE_DIC = 'invoice_dic',
    COLUMN_INVOICE_STREET = 'invoice_street',
	COLUMN_INVOICE_TOWN = 'invoice_town',
	COLUMN_INVOICE_PSC = 'invoice_psc',
    COLUMN_INVOICE_COUNTRY = 'invoice_country',
    COLUMN_DESCRIPTION = 'description';
	
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

	public function create(array $values) {
        return $this->database->table(self::TABLE_NAME)->insert($values);
    }	
}