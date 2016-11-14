<?php

namespace App\Model;

use Nette,
App\Model,
Nette\Utils\Strings;

class Orders extends Nette\Object{

	const
	TABLE_NAME = 'orders',
	COLUMN_ID = 'id',
    COLUMN_STATE = 'state',
    COLUMN_USER_STATE = 'user_state',
    COLUMN_DATE = 'date',
    COLUMN_ZP_O = 'zp_o',
    COLUMN_ZP_M = 'zp_m',
    COLUMN_ZP_P = 'zp_p',
    COLUMN_USER_ID = 'user_id',
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
    COLUMN_DESCRIPTION = 'description',
    COLUMN_TOTAL_PRICE = 'total_price',
    COLUMN_TOTAL_PRICE_VAT = 'total_price_vat',
    COLUMN_DELIVERY_PAY = 'delivery_pay',
    COLUMN_FEE_PAY = 'fee_pay',
    BALIK_DO_RUKY = 160,
    BALIK_NA_POSTU = 135,
    ODBER_V_MISTE = 60,
    DOBIRKA_LIMIT = 5000,
    DOBIRKA_1 = 50,
    DOBIRKA_2 = 63;
	
	/** @var Nette\Database\Context */
    private $database;

    /** @var \Kdyby\Translation\Translator  */
    public $translator;

    public $deliveryType = ['1' => 'Osobně', '2' => 'Osobní vyzvednutí v místě','3' => 'Česká pošta'];
    public $deliveryMailTypes = ['1' => 'Balík na poštu '.self::BALIK_NA_POSTU.',- Kč', '2' => 'Balík do ruky  '.self::BALIK_DO_RUKY.',- Kč'];
    public $deliveryPlaces = ['1' => 'Ostrava','2' => 'Frýdek-Místek','3' => 'Nový Jičín','4' => 'Olomouc','5' => 'Hranice','6' => 'Rožnov pod Radhoštěm','7' => 'Vsetín'];
    public $paymentType1 = ['1' => 'Hotově', '2' => 'Převodem na účet'];
    public $paymentType2 = ['2' => 'Převodem na účet', '3' => 'Dobírka'];

    public $state = ['1' => 'Vytvořeno', '2' => 'Přijato', '3' => 'Připraveno', '4' => 'Odesláno'];
    public $userState = ['1' => 'Vytvořeno', '2' => 'Přijato', '3' => 'Připraveno', '4' => 'Odesláno'];
    public $paymentType = ['1' => 'Hotově', '2' => 'Převodem na účet', '3' => 'Dobírka']; 
    public $deliveryPlacesA = ['1' => 'Balík na poštu ', '2' => 'Balík do ruky  ', '-1' => 'Ostrava','-2' => 'Frýdek-Místek','-3' => 'Nový Jičín','-4' => 'Olomouc','-5' => 'Hranice','-6' => 'Rožnov pod Radhoštěm','-7' => 'Vsetín'];

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
        $this->database->beginTransaction(); 
        $this->database->table(\App\Model\OrderItems::TABLE_NAME)->where(\App\Model\OrderItems::COLUMN_ORDER_ID, $key)->delete();
        $retVal =  $this->database->table(self::TABLE_NAME)->where('id', $key)->delete();
        $this->database->commit();
        return $retVal;
    }

    public function getList() {
        return $this->database->table(self::TABLE_NAME);
    }

	public function create(array $values, array $items) {
        $this->database->beginTransaction();
        $orderId = $this->database->table(self::TABLE_NAME)->insert($values);
        foreach ($items as $item) {
            $item[\App\Model\OrderItems::COLUMN_ORDER_ID] = $orderId;
            $this->database->table(\App\Model\OrderItems::TABLE_NAME)->insert($item);
        }

        $this->database->commit();
        return $orderId;
    }	
}