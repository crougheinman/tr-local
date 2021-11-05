<?php namespace App\Libraries\User;

use App\Entities\User;
use App\Models\Users;
use App\Libraries\Constants\UserTypes;

class RegistrationScreener
{
    /**
     * The details of the registering user.
     *
     * @var mixed
     */
    private $details;
    private $type;
    private $table;
    private $newUser;

    /**
     * The constructor of the screener.
     *
     * @param mixed $user
     * @param string $type
     */
    public function __construct($user)
    {
        $this->table = new Users();
        $this->details = $user;
    }

    /**
     * Try to register this user. Returns false on failed
     * and stores the errors on the table.
     *
     * @return boolean
     */
    public function register()
    {
        $user = new User();

        if (!$this->isEmailAvailable()) {
            return false;
        }

        if (!isset($this->details['type'])) {
            return false;
        }

        if (!array_key_exists(
            $this->details['type'], 
            UserTypes::USER_TYPE_NAMES)
        ) {
            return false;
        }

        $user->type = $this->details['type'];

        if ($user->type == UserTypes::USER_TYPES['AGENT']
            || $user->type == UserTypes::USER_TYPES['COMPANY']
            || $user->type == UserTypes::USER_TYPES['GROUP']
            || $user->type == UserTypes::USER_TYPES['ORGANIZATION']
        ) {
            if (!isset($this->details['rbn'])) {
                return false;
            }
            $user->username = $this->generateUsername();
        }

        //$user->rbn = $this->details['rbn'];
        $user->first_name = ucwords($this->details['first_name']);
        $user->last_name = ucwords($this->details['last_name']);
        //$user->street =  $this->details['street'];
        //$user->province = $this->details['province'];
        //$user->country = $this->details['country'];
        $user->birthdate = $this->validateBirthdate($this->details['birthdate']);
        $user->gender = $this->validateGender($this->details['gender']);
        //$user->city = $this->details['city'];
        //$user->postal_code = $this->details['postal_code'];
        $user->email = $this->details['email'];
        $user->password = password_hash($this->details['password'], PASSWORD_BCRYPT);

        if (!$this->table->save($user)) {
            return false;
        }

        $user->id = $this->table->getInsertID();
        $this->newUser = $user;

        return true;
    }

    /**
     * Validates birthdate input. The birthdate should
     * be 18 years or older.
     *
     * @param string $birthdate
     * @return string|null
     */
    public function validateBirthdate($birthdate)
    {   
        if (time() < strtotime('+18 years', strtotime($birthdate))) {
            return null;
        }

        return $birthdate;
    }

    /**
     * Validates gender input.
     *
     * @param string $gender
     * @return bool
     */
    public function validateGender($gender)
    {
        if ($gender !== 'M' && $gender !== 'F') {
            return null;
        }

        return $gender;
    }
    /**
     * Generates a username depending on the rbn.
     *
     * @return string
     */
    public function generateUsername() 
    {
        $username = preg_replace("/[^a-zA-Z0-9\s]/", "", $this->details['rbn']);
        $username = str_replace(' ', '', $username);
        $username = preg_replace('/\s+/', '', $username);

        return strtolower($username);
    }
    /**
     * Returns the errors of this screener.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->table->errors();
    }

    /**
     * Checks if the password provided is valid.
     * Minimum 8 characters are required.
     *
     * @return boolean
     */
    public function isPasswordValid()
    {
        if (strlen($this->details['password']) < 8) {
            return false;
        }

        return true;
    }

    /**
     * Returns the registered user.
     *
     * @return App\Entities\User
     */
    public function getUser()
    {
        return $this->newUser;
    }

    /**
     * Checks if this email address is available for use.
     *
     * @return boolean
     */
    public function isEmailAvailable()
    {
        $account = $this->table->where('email', $this->details['email'])
            ->where('status', 1)
            ->first();
        
        if ($account) {
            return false;
        }

        return true;
    }
}