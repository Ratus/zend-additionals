<?php
namespace ZendAdditionals\View\Helper;

use \Zend\ServiceManager\ServiceLocatorAwareInterface;
use \Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Helper for date select elements
 */
class HtmlDateSelect extends \Zend\View\Helper\AbstractHtmlElement implements
    ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Generates three 'Select' elements based on a date and a date format.
     *
     * @param string  $date         string with a (birth)date according to the $inputFormat
     * @param array   $attributes   array with attributes for a hidden div with the concatenated
     *                              date of birth value
     * @param string  $inputFormat  string with your input date format
     * @param string  $outputFormat string with your output date format
     *                              you can leave it empty if it's the same as the input format
     * @param integer $minimumAge   integer with the minimum age, this makes sure you cannot select
     *                              a year that is past the birthyear of someone with this age
     * @param integer $maximumAge   integer with the maximum age, this makes sure you cannot select
     *                              a year that is before the birthyear of someone with this age
     * @param  string $divWrapClass Wraps a div around all created select elements
     *                              The value is used for the classname
     *                              Set explicitly to null when no wrapper is wanted
     *
     * @return string The select XHTML.
     */
    public function __invoke(
        $date         = null,
        $attributes   = false,
        $inputFormat  = 'Y-m-d',
        $outputFormat = null,
        $minimumAage  = 18,
        $maximumAge   = 120,
        $divWrapClass = 'select'
    ) {
        $translationPrefix = 'my_profile.helpers.htmldateselect.';
        $attributes        = $attributes ?: array();
        $dateTime          = null;
        if (!empty($date)) {
            $dateTime = \DateTime::createFromFormat($inputFormat, $date);
        }

        $maximumDate = new \DateTime();
        $minimumDate = new \DateTime();

        $maximumDate = $maximumDate->sub(new \DateInterval("P{$minimumAage}Y"));
        $minimumDate = $minimumDate->sub(new \DateInterval("P{$maximumAge}Y"));

        $selects  = array();
        $defaults = array(
            'Y' => ((null !== $dateTime) ? $dateTime->format('Y') : null),
            'm' => ((null !== $dateTime) ? $dateTime->format('m') : null),
            'd' => ((null !== $dateTime) ? $dateTime->format('d') : null),
        );

        $hiddenInputIdentifier = 'date_select_' . mt_rand(10000, 99999);

        $subAttributes = array(
            'Y' => array(
                'class'         => 'date_select_year trigger_change',
                'change_target' => $hiddenInputIdentifier,
            ),
            'm' => array(
                'class'         => 'date_select_month trigger_change',
                'change_target' => $hiddenInputIdentifier,
            ),
            'd' => array(
                'class'         => 'date_select_day trigger_change',
                'change_target' => $hiddenInputIdentifier,
            ),
        );
        $translator = $this->getServiceLocator()->get('translate');

        for ($month = 1; $month <= 12; ++$month) {
            $month                = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            $selects['m'][$month] = $month;
        }

        for ($day = 1; $day <= 31; ++$day) {
            $day                = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $selects['d'][$day] = $day;
        }

        for (
            $year = $minimumDate->format('Y');
            $year <= $maximumDate->format('Y');
            $year++
        ) {
            $selects['Y'][$year] = (string) $year;
        }

        $selects['Y']       = array_reverse($selects['Y'], true);
        $outputFormat       = $outputFormat ?: $inputFormat;

        // Generate the hidden input variable based on the inputFormat
        $inputFormatParts   = explode('-', $inputFormat);
        $defaultValueString = '';
        $first              = true;
        foreach ($inputFormatParts as $formatPart) {
            $defaultValueString .= (
                ($first ? '' : '-') .
                "{$defaults[$formatPart]}"
            );
            $first = false;
        }

        $wrapClassAppends = array(
            'Y' => 'date_select_year',
            'm' => 'date_select_month',
            'd' => 'date_select_day',
        );

        $formatParts = explode('-', $outputFormat);
        $return      = '<div class="date_select date_select_' . $hiddenInputIdentifier . '">' .
                       '<input id="' . $hiddenInputIdentifier . '" type="hidden" value="' . $defaultValueString .
                       '" ' . $this->htmlAttribs($attributes) . '/>';

        for ($i = 0, $s = sizeof($formatParts); $i < $s; ++$i) {
            $formatPart = $formatParts[$i];
            $htmlSelect = new HtmlSelect;
            $htmlSelect->setView($this->getView());
            $select     = $selects[$formatPart];
            $return    .= $htmlSelect(
                $select,
                $subAttributes[$formatPart],
                $defaults[$formatPart],
                null,
                $divWrapClass . (!empty($divWrapClass) ? ' ' : '') . $wrapClassAppends[$formatPart]
            );
            if (($i+1) < $s) {
                $return .= '<div class="date_select_separator"></div>';
            }
        }

        $return .=
            "<script type='text/javascript'>\n" .
            "    $('.date_select_{$hiddenInputIdentifier}').find('select').on(\n" .
            "        'change',\n" .
            "        function() {\n" .
            "            var dateValues = new Array();\n";

        $namedFormats = array(
            'Y' => 'year',
            'm' => 'month',
            'd' => 'day',
        );
        $count = 0;
        foreach ($inputFormatParts as $inputFormatPart) {
            $return .=
                "            dateValues[{$count}] = $(this).closest('div.date_select').find('select.date_select_" .
                "{$namedFormats[$inputFormatPart]}').val();\n";
            $count++;
        }

        $return .=
            "            $('#{$hiddenInputIdentifier}').val(dateValues.join('-')).trigger('change');\n" .
            "        }\n" .
            "    );\n" .
            "</script>\n" .
            "</div>\n";

        return $return;
    }
}
