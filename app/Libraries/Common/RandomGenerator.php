<?php namespace App\Libraries\Common;
class RandomGenerator
{
    /**
     * The set of characters to be used in string generation.
     */
    const CHARACTERS = [
        'numbers' => '12345678901234567890',
        'letters' => 'abcdefghijklmnopqrstuvwxyz'
    ];

    /**
     * Generates a random string.
     *
     * @param integer $length
     * @param boolean $alphanum
     * @return void
     */
    public static function generate($length = 18, $alphanum = false)
    {
        $ref = self::CHARACTERS['letters'];

        if ($alphanum) {
            $ref = $ref . self::CHARACTERS['numbers'];
        }

        $randomid = array();
        $numlen = strlen($ref);

        for ($i = 0; $i < $length; $i++) {
            $randomid[$i] = $ref[rand(0, $numlen - 1)];
        }

        return implode($randomid);
    }
}