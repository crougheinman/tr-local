<?php namespace App\Libraries\Common;
/**
 *
 * Date Helpers
 *
 * A simple library to assist the developer
 * with some useful functions regarding dates.
 *
 * @package App.Libraries
 * @author Bill Dwight Ijiran <dwight.ijiran@gmail.com>
 */
class DateHelper
{
    /**
     * Checks a date if it already
     * passed versus today.
     *
     * @param $date The date to check.
     * @return boolean Returns true otherwise false.
     */
    public function isInPast($date)
    {
        $date = strtotime($date);
        if($date < time()) {
            return true;
        }
        return false;
    }

    /**
     * Add a day from today and return the date.
     *
     * The second parameter lets you select which date
     * you want to add a day from.
     *
     * @param int $daysToAdd The number of days to add.
     * @param date|null $dateToAddFrom The date to add from.
     * Defaults to date today when null.
     * @return date Returns the date.
     */
    public function addDays($daysToAdd, $dateToAddFrom = null)
    {
        if ($dateToAddFrom === null) {
            $dateToAddFrom = date('Y-m-d H:i:s');
        }
        return date(
            'Y-m-d H:i:s',
            strtotime(
                $dateToAddFrom . ' + ' . $daysToAdd . ' days'
            )
        );
    }

    /**
     * Counts the number of minutes passed from
     * the date provided.
     *
     * @param $date The date to count from.
     * @return int Returns the number of minutes.
     */
    public function countMinutesPassed($date)
    {
        $to_time = strtotime(date('Y-m-d H:i:s'));
        $from_time = strtotime($date);
        return round(abs($to_time - $from_time) / 60,2);
    }

    /**
     * Counts the number of days passed from
     * the date provided.
     *
     * @param $date The date to count from.
     * @return int Returns the number of days.
     */
    public function countDaysGone($date)
    {
        $date1 = new \DateTime($date);
        $date2 = new \DateTime(date('Y-m-d'));
        return $date2->diff($date1)->format('%a');
    }
}
