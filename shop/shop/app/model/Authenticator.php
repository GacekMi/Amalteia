<?php

namespace App\Model;

use Nette,
Nette\Utils\Strings,
Nette\Security\Passwords,
Nette\Security;

/** * Users authenticator. */
class Authenticator extends Nette\Object implements Security\IAuthenticator {
	
	const
	TABLE_NAME = 'users',
	COLUMN_ID = 'id',
	COLUMN_PARTNER_ID = 'partner_id',
	COLUMN_FIRST_NAME = 'first_name',
	COLUMN_LAST_NAME = 'last_name',
	COLUMN_EMAIL = 'email',
	COLUMN_PHONE = 'phone',
	COLUMN_BIRTH_DATE = 'birth_date',
	COLUMN_PASSWORD_HASH = 'pass',
	COLUMN_VIP_DATE = 'vip_date',
	COLUMN_PERSONAL_ID = 'personal_id',
	COLUMN_TOKEN = 'token',
	COLUMN_TOKEN_TYPE = 'token_type',
	COLUMN_STATE = 'state',
	COLUMN_LAST_LOGIN = 'last_login',
	COLUMN_REGISTERED = 'registered',
	COLUMN_ROLE = 'role';
	
	/** @var Nette\Database\Context */
    private $database;

    /** @var \Kdyby\Translation\Translator  */
    public $translator;

    public function __construct(Nette\Database\Context $database, Nette\Localization\ITranslator $translator) {
        $this->database = $database;
        $this->translator = $translator;
    }

    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials) {
        list($username, $password) = $credentials;

        $row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_EMAIL, $username)->fetch();

        if (!$row) {
            throw new Nette\Security\AuthenticationException($this->translator->translate("ui.signMessage.loginIncorect"), self::IDENTITY_NOT_FOUND);
        } elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
            throw new Nette\Security\AuthenticationException($this->translator->translate("ui.signMessage.loginIncorect"), self::INVALID_CREDENTIAL);
        } elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
            $row->update(array(
                self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
            ));
        }

        if ($row[self::COLUMN_STATE] == 0) {
            throw new Nette\Security\AuthenticationException($this->translator->translate("ui.signMessage.userIsBlocked"), self::INVALID_CREDENTIAL);
        }

        $arr = $row->toArray();
        unset($arr[self::COLUMN_PASSWORD_HASH]);
        return new Nette\Security\Identity($row[self::COLUMN_ID], explode(",", $row[self::COLUMN_ROLE]), $arr);
    }

    function update($key, $values) {
         return $this->database->table(self::TABLE_NAME)->where('id', $key)->update($values);
    }
    
    public function get($key) {
        return $this->database->table(self::TABLE_NAME)->get($key);
    }

    public function getByEmail($email)
    {
       return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_EMAIL, $email)->fetch();
    }

    public function getByPersonalId($rc)
    {
         return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_PERSONAL_ID, $rc)->fetch();
    }

    public function delete($key) {
        return $this->database->table(self::TABLE_NAME)->where('id', $key)->delete();
    }

    public function getList() {
        return $this->database->table(self::TABLE_NAME);
    }

    public function saveLoginDateTime($key) {
        $dt = new \DateTime();
        return $this->database->table(self::TABLE_NAME)->where('id', $key)->update(array(
                    self::COLUMN_LAST_LOGIN => $dt,
        ));
    }

    public function setUserState($key, $state) {
        return $this->database->table(self::TABLE_NAME)->where('id', $key)->update(array(
                    self::COLUMN_STATE => $state,
        ));
    }
    
    public function verifiPassword($passwd1, $passwd2)
    {
        return Passwords::verify($passwd1, $passwd2);
    }

    public function isAdminRow($key, $roles)
    {
        $row = $this->database->table(self::TABLE_NAME)->get($key);
        if(strpos($row['role'], 'admin') && !strpos($roles, 'admin'))
        {
            return true;
        }
        
        return false;
    }

    public function changePassword($key, $pass)
    {
       return $this->database->table(self::TABLE_NAME)->where('id', $key)->update(array(
            self::COLUMN_PASSWORD_HASH => Passwords::hash($pass)));
    }

    function getRandomBytes($nbBytes = 32) {
        $bytes = openssl_random_pseudo_bytes($nbBytes, $strong);
        if (false !== $bytes && true === $strong) {
            return $bytes;
        } else {
            throw new \Exception($this->translator->translate("ui.signMessage.exceptionRandom"));
        }
    }

    public function generateToken($length) {
        return substr(preg_replace("/[^a-zA-Z0-9]/", "", base64_encode($this->getRandomBytes($length + 1))), 0, $length);
    }

    public function resetUserPassword($key) {
        //Doplnit ma se nastavit token a typ tokenu a vratit ten token a pak vygenerovat email a taky
        //nastavit state na prislusnou hodnotu, vse by mel resit pak pri prohlaseni
        $resetPass = $this->generatePassword(8);
        $res = $this->database->table(self::TABLE_NAME)->where('id', $key)->update(array(
            self::COLUMN_PASSWORD_HASH => Passwords::hash($resetPass),
        ));
        if ($res > 0) {
            return $resetPass;
        }

        return 0;
    }   

    public function createUser(array $values) {
        $values[self::COLUMN_PASSWORD_HASH] = Passwords::hash($values[self::COLUMN_PASSWORD_HASH]);
        $values[self::COLUMN_STATE] = 0;
        $values[self::COLUMN_ROLE] = 'user';
        $values[self::COLUMN_REGISTERED] = new \DateTime();
        return $this->database->table(self::TABLE_NAME)->insert($values);
    }



    //ZBYTKAC ....
    public function edit($key, $username, $password, $roles, $email, $state) {
        //nevim zda to pouziji jestli nebude stacit jen update
        if (isset($password)) {
            return $this->database->table(self::TABLE_NAME)->where('id', $key)->update(array(
                        self::COLUMN_NAME => $username,
                        self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
                        self::COLUMN_ROLE => $roles,
                        self::COLUMN_EMAIL => $email,
                        self::COLUMN_STATE => $state,
            ));
        } else {
            return $this->database->table(self::TABLE_NAME)->where('id', $key)->update(array(
                        self::COLUMN_NAME => $username,
                        self::COLUMN_ROLE => $roles,
                        self::COLUMN_EMAIL => $email,
                        self::COLUMN_STATE => $state,
            ));
        }
    }


    
	
}


