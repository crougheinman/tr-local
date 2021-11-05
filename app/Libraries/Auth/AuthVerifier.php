<?php namespace App\Libraries\Auth;

use App\Models\Clients;
use App\Libraries\Constants\ClientTypes;
use App\Libraries\Common\DateHelper;

/**
 * A class that accepts an Authorization header and reads it out whether
 * it is valid, invalid or expired.
 */
class AuthVerifier
{
    const AUTHORIZATION_STATE = [
        'EXPIRED' => 2,
        'INVALID' => 0,
        'VALID' => 1
    ];

    const AUTHORIZATION_VALUES = [
        'CLIENT_TYPE' => 0,
        'OWNER_ID' => 1,
        'TOKEN' => 2
    ];

    private $state;
    private $client;
    public function __construct($authorization)
    {
        $this->state = 0;
        $this->authorization = explode(':', $authorization)[1];
        $this->authorization = $this->decode();
        if (!$this->authorization) {
            $this->state = $this::AUTHORIZATION_STATE['INVALID'];
            return;
        }
        $this->table = new Clients();
        $this->client = $this->load();
        if (!$this->client) {
            $this->state = $this::AUTHORIZATION_STATE['INVALID'];
            return;
        }
        $dateHelper = new DateHelper();
        if ($dateHelper->isInPast($this->client['expires_at'])) {
            $this->state = $this::AUTHORIZATION_STATE['EXPIRED'];
            return;
        }
        if ($this->client['token'] != $this->authorization[self::AUTHORIZATION_VALUES['TOKEN']]) {
            $this->deleteFromCache();
            $this->state = $this::AUTHORIZATION_STATE['INVALID'];
            return;
        }

        $this->state = $this::AUTHORIZATION_STATE['VALID'];
        $this->saveToCache();
    }

    /**
     * Determins if the client is expired.
     */
    public function isExpired()
    {

    }

    /**
     * Determines if the authorization client is invalid.
     *
     * @return boolean
     */
    public function isInvalid()
    {
        if ($this->state == $this::AUTHORIZATION_STATE['INVALID']) {
            return true;
        }

        return false;
    }

    /**
     * Decodes the given authorization.
     *
     * @return array
     */
    private function decode()
    {
        //try {
            $source = base64_decode($this->authorization);
            $source = explode(':', $source);
            if (!isset($source[$this::AUTHORIZATION_VALUES['CLIENT_TYPE']]) 
                || !isset($source[$this::AUTHORIZATION_VALUES['OWNER_ID']])
                || !isset($source[$this::AUTHORIZATION_VALUES['TOKEN']])) {
                return null;
            }
            if (!$this->authorization) {
                return null;
            }
        // } catch (\Exception $e) {
        //     return null;
        // }   

        return $source;
    }

    /**
     * Gets the client type. Either user, admin or sub-user
     *
     * @return void
     */
    public function getClientType()
    {
        return $this->authorization[$this::AUTHORIZATION_VALUES['CLIENT_TYPE']];
    }

    /**
     * Gets the user id if use or admin id if admin.
     *
     * @return int
     */
    public function getOwnerId()
    {
        return $this->authorization[$this::AUTHORIZATION_VALUES['OWNER_ID']];
    }

    /**
     * Gets the token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->authorization[$this::AUTHORIZATION_VALUES['TOKEN']];
    }

    /**
     * Loads the client record either from cache or database.
     *
     * @return array
     */
    public function load()
    {
        $record = $this->loadFromCache();

        if (!$record) {
            $record = $this->loadFromDatabase();
        }
 
        return $record;
    }

    /**
     * Tries to load the authorization record of user/admi
     * from the cache.
     *
     * @return void
     */
    private function loadFromCache()
    {
        $cache = \Config\Services::cache();
        $record = null;
        if ($this->getClientType() == ClientTypes::CLIENT_TYPE['ADMIN']) {
            $record = $cache->get('A' . $this->getOwnerId());

            if (!$record) {
                return null;
            }
        }
        if ($this->getClientType() == ClientTypes::CLIENT_TYPE['USER']) {
            $record = $cache->get('U' . $this->getOwnerId());

            if (!$record) {
                return null;
            }
        }
        if ($this->getClientType() == ClientTypes::CLIENT_TYPE['SUBUSER']) {
            $record = $cache->get('SU' . $this->getOwnerId());

            if (!$record) {
                return null;
            }
        }

        return json_decode($record, true);
    }

    /**
     * Load client authorization from database.
     *
     * @return App\Entities\Client
     */
    private function loadFromDatabase()
    {
        $record = $this->table->where('type', $this->getClientType())
            ->where('owner_id', $this->getOwnerId())
            ->where('token', $this->getToken())
            ->first();

        if (!$record) {
            return null;
        }

        return $record->toArray();
    }

    /**
     * Saves a copy of the client to the cache for quick access.
     *
     * @return void
     */
    private function saveToCache()
    {
        $name = $this->getCacheName();
        if (!cache($name)) {
            $value = json_encode($this->client);
            cache()->save($name, $value, 600);
        }
    }

    /**
     * Deletes current cache for this user.
     */
    private function deleteFromCache()
    {
        $name = $this->getCacheName();
        if (cache($name)) {
            cache()->delete($name);
        }
    }

    /**
     * Gets the cache name.
     *
     * @return string
     */
    private function getCacheName()
    {
        $prefix = 'A';
        $clientType = $this->getClientType();
        switch ($clientType) {
            case ClientTypes::CLIENT_TYPE['ADMIN']:
                $prefix = 'A';
            break;
            case ClientTypes::CLIENT_TYPE['USER']:
                $prefix = 'U';
            break;
            case ClientTypes::CLIENT_TYPE['SUBUSER']:
                $prefix = 'SU';
            break;
        }

        return $prefix . $this->getOwnerId();
    }
}