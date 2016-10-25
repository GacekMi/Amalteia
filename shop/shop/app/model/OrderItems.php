<?php

namespace App\Model;

use Nette,
App\Model,
Nette\Utils\Strings;

class OrderItems extends Nette\Object{

	const
	TABLE_NAME = 'orderitems',
	COLUMN_ID = 'id';
	
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