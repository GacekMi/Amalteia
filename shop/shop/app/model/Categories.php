<?php

namespace App\Model;

use Nette,
Nette\Utils\Strings;

class Categories extends Nette\Object{

	const
	TABLE_NAME = 'categories',
	COLUMN_ID = 'id',
    COLUMN_SUB_ID = 'sub_id',
    COLUMN_ORD = 'ord',
	COLUMN_LABEL = 'label';
	
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