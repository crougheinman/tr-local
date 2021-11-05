<?php namespace App\Libraries\Auth;

use App\Models\{
    Admins,
    Users,
    Clients
};

use App\Entities\Client;
use App\Libraries\Constants\{ClientTypes, StatusTypes};
use App\Libraries\Common\{RandomGenerator, DateHelper};

/**
 * An object responsible for authenticating users.
 */
class Authenticator
{
    private $identifier;
    private $password;
    private $keepMeLoggedIn;
    private $user;
    private $table;
    private $clientsTable;
    private $loginStatus;
    private $randomGenerator;
    private $type;
    private $identifierColumn;
    private $ipAddress;
    private $userAgent;
    private $base64token;

    /**
     * Defines the login status string value on int.
     */
    const LOGIN_STATUS = [
        'ACCOUNT_NOT_FOUND' => 0,
        'LOGIN_SUCCESS' => 1,
        'EMAIL_NEEDS_VERIFICATION' => 2,
        'PASSWORD_INCORRECT' => 3,
        'ACCOUNT_BANNED' => 4,
        'INTERNAL_ERROR' => 5,
        'ADMIN_DOCUMENT_PROCESS' => 6, 
        'ADMIN_PAYMENT_PROCESS' => 7,
    ];

    /**
     * Defines the login status integer meaning.
     */
    const LOGIN_STATUS_NAMES = [
        0 => 'ACCOUNT_NOT_FOUND',
        1 => 'LOGIN_SUCCESS',
        2 => 'EMAIL_NEEDS_VERIFICATION',
        3 => 'PASSWORD_INCORRECT',
        4 => 'ACCOUNT_BANNED',
        5 => 'INTERNAL_ERROR',
        6 => 'ADMIN_DOCUMENT_PROCESS',
        7 => 'ADMIN_PAYMENT_PROCESS'
    ];

    /**
     * Receives credentials. Can be null at the time
     * of the construction.
     *
     * @param array $credentials
     */
    public function __construct($request = null, $type = ClientTypes::CLIENT_TYPE['USER'])
    {
        $credentials = $request->getJSON(true);
        $this->ipAddress = $request->getIPAddress();
        $this->userAgent = $request->getUserAgent();
        $this->identifier = $credentials['identifier'];
        $this->password = $credentials['password'];
        $this->keepMeLoggedIn = ($credentials['keepMeLoggedIn']) ?? false;
        $this->type = $type;
        $this->base64token = null;

        if ($type == ClientTypes::CLIENT_TYPE['USER']) {
            $this->table = new Users();
        } else if ($type == ClientTypes::CLIENT_TYPE['ADMIN']) {
            $this->table = new Admins();
        } else {
            $this->table = new Users();
        }

        $this->clientsTable = new Clients();
        $this->randomGenerator = new RandomGenerator();
    }

    /**
     * Manually set the credentials of this authenticator.
     *
     * @param string $identifier
     * @param string $password
     * @param boolean $keepMeLoggedIn
     * @return void
     */
    public function setCredentials($identifier, $password, $keepMeLoggedIn = false)
    {
        $this->identifier = $identifier;
        $this->password = $password;
        $this->keepMeLoggedIn = $keepMeLoggedIn;
    }

    /**
     * Logs in the given credentials.
     *
     * @return void
     */
    public function login()
    {
        $this->identifierColumn = 'username';
        
        if ($this->isEmail()) {
            $this->identifierColumn = 'email';
        }

        $accounts = $this->table->where($this->identifierColumn, $this->identifier)
            ->findAll();

        if (!$accounts) {
            $this->loginStatus = self::LOGIN_STATUS['ACCOUNT_NOT_FOUND'];

            return false;
        }

        $targetAccount = null;

        if (count($accounts) > 1) {
            foreach ($accounts as $account) {
                if (password_verify($this->password, $account->password)) {
                    $targetAccount = $account;
                    break;
                }
            }
        } else {
            if (!password_verify($this->password, $accounts[0]->password)) {
                $this->loginStatus = self::LOGIN_STATUS['PASSWORD_INCORRECT'];
                
                return false;
            }
            $targetAccount = $accounts[0];
        }

        if ($targetAccount === null) {
            $this->loginStatus = self::LOGIN_STATUS['ACCOUNT_NOT_FOUND'];

            return false;
        }

        if ($targetAccount->status == StatusTypes::STATUS_TYPES['NOT_ACTIVATED']) {
            $this->loginStatus = self::LOGIN_STATUS['EMAIL_NEEDS_VERIFICATION'];

            return false;
        }

        if ($targetAccount->status == StatusTypes::STATUS_TYPES['BANNED']) {
            $this->loginStatus = self::LOGIN_STATUS['ACCOUNT_BANNED'];
            
            return false;
        }

        if ($targetAccount->status == StatusTypes::STATUS_TYPES['UNDER_DOCUMENT_PROCESSING']) {
            $this->loginStatus = self::LOGIN_STATUS['ADMIN_DOCUMENT_PROCESS'];
            
            return false;
        }

        if ($targetAccount->status == StatusTypes::STATUS_TYPES['UNDER_PAYMENT_PROCESSING']) {
            $this->loginStatus = self::LOGIN_STATUS['ADMIN_PAYMENT_PROCESS'];
            
            return false;
        }

        $this->user = $targetAccount;

        if ($this->createClient()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the identifier is an email.
     *
     * @return boolean
     */
    private function isEmail()
    {
        if (filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the login was success.
     *
     * @return void
     */
    public function success()
    {
        if ($this->loginStatus == self::LOGIN_STATUS['LOGIN_SUCCESS']) {
            return true;
        }

        return false;
    }

    /**
     * Creates a new authorization client for the 
     * user.
     *
     * @param mixed $user
     * @return void
     */
    private function createClient()
    {
        $client = new Client();
        $client->type = $this->type;
        $client->owner_id = $this->user->id;
        $client->token = $this->generateToken();
        $client->user_agent = $this->userAgent;
        $client->ip_address = $this->ipAddress;
        $client->expires_at = $this->getExpiration();

        if (!$this->clientsTable->save($client)) {
            return false;
        }
        $this->base64token = $this->generateBase64Token($client);

        return true;
    }

    /**
     * Generates a base 64 based on a client record.
     *
     * @param App\Entities\Client $client
     * @return string
     */
    private function generateBase64Token($client) {
        $source = $client->type . ':' . $client->owner_id . ':' . $client->token;
        
        return base64_encode($source);
    }

    /**
     * Gets the generated base64 token of this authenticator.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->base64token;
    }

    /**
     * Generates a token for the client.
     *
     * @return string
     */
    private function generateToken()
    {
        $this->randomGenerator = new RandomGenerator();

        return $this->randomGenerator->generate(128, true);
    }

    /**
     * Returns an expiration date.
     *
     * @return void
     */
    private function getExpiration()
    {
        $dateHelper = new DateHelper();

        if ($this->keepMeLoggedIn) {
            return $dateHelper->addDays(365);
        }

        return $dateHelper->addDays(1);
    }
    /**
     * Returns true if the password was incorrect on login.
     *
     * @return boolean
     */
    public function isPasswordIncorrect()
    {
        if ($this->loginStatus == self::LOGIN_STATUS['PASSWORD_INCORRECT']) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if account was not found.
     *
     * @return boolean
     */
    public function isAccountNotFound()
    {
        if ($this->loginStatus == self::LOGIN_STATUS['ACCOUNT_NOT_FOUND']) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if account was banned.
     *
     * @return boolean
     */
    public function isAccountBanned()
    {
        if ($this->loginStatus == self::LOGIN_STATUS['ACCOUNT_BANNED']) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if email still needs verification.
     *
     * @return boolean
     */
    public function isEmailNeedsVerification()
    {
        if ($this->loginStatus == self::LOGIN_STATUS['EMAIL_NEEDS_VERIFICATION']) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the account is on document processing.
     *
     * @return boolean
     */
    public function isUnderDocumentProcessing()
    {
        if ($this->loginStatus == self::LOGIN_STATUS['ADMIN_DOCUMENT_PROCESS']) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the account is on payment processing.
     *
     * @return boolean
     */
    public function isUnderPaymentProcessing()
    {
        if ($this->loginStatus == self::LOGIN_STATUS['ADMIN_PAYMENT_PROCESS']) {
            return true;
        }

        return false;
    }

    /**
     * Gets the currently logged in user.
     *
     * @return App\Entities\User
     */
    public function getUser()
    {
        unset($this->user->password);
        unset($this->user->verification_token);

        return $this->user;
    }
}