<?php
namespace ZendAdditionals\View\Helper;

use DateTime;

/**
 * Helper to get the age form a date provided in a custom format
 * 
 * @category   ZendAdditionals
 * @package    View
 * @subpackage Helper
 */
class Age extends \Zend\View\Helper\AbstractHtmlElement
{
    /**
     * Calculates the age on the given date
     *
     * @param  string $date   The date
     * @param  string $format The format used by $date e.g. 'Y-m-d'
     *
     * @return string The age calculated form the date, 'err' on failure
     */
    public function __invoke(
        $date,
        $format = 'Y-m-d'
    ) {
        $dateTime = DateTime::createFromFormat($format, $date);
        if (!($dateTime instanceof DateTime)) {
            return 'err';
        }
        return $dateTime->diff(new DateTime())->y;
    }
}
