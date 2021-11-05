<?php namespace App\Libraries\User;

use App\Models\Users;
use App\Libraries\Constants\{ UserTypes, StatusTypes };

class UserEmailVerifier
{
    const STATE_NAMES = [
        0 => 'Invalid',
        1 => 'Success',
        2 => 'Expired'
    ];
    private $state = 0;
    private $table = null;
    private $user = null;
    private $errors = null;

    public function __construct($request)
    {
        $this->table = new Users();
        $this->user = $this->table->where('id', $request['id'])
            ->where('verification_token', $request['verification_token'])
            ->where('status', 0)
            ->where('verified_at IS NULL')
            ->first();

        if (!$this->user) {
            $this->state = 0;
        } else {
            $this->user->verified_at = date('Y-m-d H:i:s');

            if ($this->user->type == UserTypes::USER_TYPES['AGENT'] 
                || $this->user->type == UserTypes::USER_TYPES['GROUPS']
            ) {
                $this->user->status = StatusTypes::STATUS_TYPES['UNDER_DOCUMENT_PROCESSING'];
            } else {
                $this->user->status = StatusTypes::STATUS_TYPES['ACTIVATED'];
            }

            if (!$this->table->save($this->user)) {
                $this->state = -1;
                $this->errors = $this->table->errors();
            }

            $this->flushSimilarEmails();
            $this->state = 1;
        }
    }

    /**
     * Checks if the email has been verified.
     *
     * @return boolean
     */
    public function isSuccess()
    {
        if ($this->state == 1) {
            return true;
        }

        return false;
    }

    /**
     * Gets the current user.
     *
     * @return App\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Gets errors from verifying.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Deletes other similar emails.
     *
     * @return void
     */
    public function flushSimilarEmails()
    {
        $this->table->where('email', $this->user->email)
            ->where('status', 0)
            ->delete();

        $this->table->purgeDeleted();
    }
}