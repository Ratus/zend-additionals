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
        $attributes = $attributes ?: array();
        $dateTime = null;
        if (!empty($date)) {
            $dateTime = \DateTime::createFromFormat($inputFormat, $date);
        }

        $ngModelNames = array(
            'Y',
            'm',
            'd'
        );

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
            $month = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            $selects['m'][$month] = $month;
        }

        for ($day = 1; $day <= 31; ++$day) {
            $day = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $selects['d'][$day] = $day;
        }

        for (
            $year = $minimumDate->format('Y');
            $year <= $maximumDate->format('Y');
            $year++
        ) {
            $selects['Y'][$year] = (string) $year;
        }

        $selects['Y'] = array_reverse($selects['Y'], true);

        $outputFormat = $outputFormat ?: $inputFormat;

        // Generate the hidden input variable based on the ng model names
        $inputFormatParts = explode('-', $inputFormat);
        $defaultValueString = '';
        $first = true;
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
        $return = '<div class="date_select date_select_' . $hiddenInputIdentifier . '">';
        $return .= '<input id="' . $hiddenInputIdentifier . '" type="hidden" value="' . $defaultValueString . '" ' . $this->htmlAttribs($attributes) . '/>';

        for ($i = 0, $s = sizeof($formatParts); $i < $s; ++$i) {
            $formatPart = $formatParts[$i];
            $htmlSelect = new HtmlSelect;
            $htmlSelect->setView($this->getView());
            $select = $selects[$formatPart];
            $return .= $htmlSelect(
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
            "<script type='text/javascript'>
                $('.date_select_{$hiddenInputIdentifier}').find('select').on(
                    'change',
                    function() {";

        $namedFormats = array(
            'Y' => 'year',
            'm' => 'month',
            'd' => 'day',
        );
        $count = 0;
        $return .= "
            var dateValues = new Array();";
        foreach ($inputFormatParts as $inputFormatPart) {
            $return .= "
                console.log('select.date_select_{$namedFormats[$inputFormatPart]}');
                dateValues[{$count}] = $(this).closest('div.date_select').find('select.date_select_{$namedFormats[$inputFormatPart]}').val();
            ";
            $count++;
        }

        $return .= "
            $('#{$hiddenInputIdentifier}').val(dateValues.join('-')).trigger('change');
                    }
                );
            </script>";

        return $return . '</div>';
    }
}
