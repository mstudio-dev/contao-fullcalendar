<?php

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;

$GLOBALS['TL_DCA']['tl_calendar']['palettes']['default'] .= ';{fullcal_legend:hide},fullcal_alias,fullcal_color,fullcal_type';
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'fullcal_type';

$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['fullcal_type_webdav'] = 'fullcal_baseUri,fullcal_path,fullcal_username,fullcal_password,fullcal_range';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['fullcal_type_public_ics'] = 'fullcal_ics,fullcal_range';

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_range'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['3_months', '6_months', '1_year', '2_years'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['fullcal_range'],
    'eval' => ['tl_class' => 'w50', 'mandatory' => true],
    'sql' => "varchar(255) NOT NULL default 'next_365'",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_alias'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'alias', 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
    'sql' => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_color'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 6, 'multiple' => true, 'size' => 2, 'colorpicker' => true, 'isHexColor' => true, 'decodeEntities' => true, 'tl_class' => 'w50 wizard'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_type'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['webdav', 'public_ics'],
    'reference' => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal_type'],
    'sql' => "varchar(16) NOT NULL default ''",
    'eval' => [
        'submitOnChange' => true,
        'tl_class' => 'clr',
        'includeBlankOption' => true,
    ],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_baseUri'] = [
    'exclude' => true,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['mandatory' => true, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_ics'] = [
    'exclude' => true,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['mandatory' => true, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_path'] = [
    'exclude' => true,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['mandatory' => true, 'tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_username'] = [
    'exclude' => true,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_password'] = [
    'exclude' => true,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_lastchanged'] = [
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
