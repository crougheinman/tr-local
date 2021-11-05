<?php namespace App\Libraries\User;

use App\Libraries\Common\RandomGenerator;
use App\Models\Users;

class UserEmailSender
{
    private $user;
    private $table;
    private $emailService;
    public function __construct($user, $newUser = true)
    {
        $this->emailService = \Config\Services::email();
        $this->user = $user;

        if ($newUser) {
            $this->user->verification_token = RandomGenerator::generate(32, true);
        }

        $this->table = new Users();
    }

    /**
     * Sends a verification email to the user.
     *
     * @return boolean
     */
    public function sendVerificationEmail()
    {
        if (!$this->table->save($this->user)) {
            return false;
        }

        $this->emailService->setFrom(getenv('app.email'), getenv('app.title'));
        $this->emailService->setTo($this->user->email);
        $this->emailService->setSubject(getenv('app.title') . ' Email Verification');
        $this->emailService->setMailType('html');
        $this->emailService->setMessage(view('emails/verification', [
            'name' => $this->user->first_name . ' ' . $this->user->last_name,
            'link' => $this->generateVerificationLink()
        ]));
        if (!$this->emailService->send()) {
            return false;
        }

        return true;
    }

    /**
     * Generate a verification link.
     */
    private function generateVerificationLink()
    {
        return getenv('app.appUrl') . 'verification?id='
            . $this->user->id
            . '&verification_token=' . $this->user->verification_token;
    }
}