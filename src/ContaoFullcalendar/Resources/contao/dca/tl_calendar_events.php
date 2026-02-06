<?php

use Contao\CalendarEventsModel;
use Contao\Input;

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['fullcal_uid'] = [
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['doNotCopy' => true],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['fullcal_id'] = [
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['doNotCopy' => true],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['fullcal_desc'] = [
    'sql' => "text NULL",
    'eval' => ['doNotCopy' => true],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['fullcal_ics'] = [
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['doNotCopy' => true],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['fullcal_cat'] = [
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['doNotCopy' => true],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['fullcal_detailViewer'] = [
    'inputType' => 'fullcalView',
    'sql' => "char(1) NOT NULL default ''",
    'eval' => ['doNotCopy' => true],
];

