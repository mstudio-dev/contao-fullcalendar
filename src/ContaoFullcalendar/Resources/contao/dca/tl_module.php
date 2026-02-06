<?php

use Contao\Backend;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_module']['palettes']['fullcalendar'] = '{title_legend},name,headline,type;{source_legend},cal_calendar;{fullcal_legend},fullcal_range,cal_startDay,fullcal_weekNumbers,fullcal_fixedWeekCount;fullcal_contentHeight,fullcal_aspectRatio,fullcal_wrapTitleMonth,fullcal_isRTL;fullcal_headerToolbar_start,fullcal_headerToolbar_center,fullcal_headerToolbar_end;fullcal_tooltip_options,fullcal_options_additional;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_range'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['3_months', '6_months', '1_year', '2_years'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['fullcal_range'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default '1_year'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_weekNumbers'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_fixedWeekCount'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_contentHeight'] = [
    'exclude' => true,
    'default' => '',
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_aspectRatio'] = [ // 1.35
    'exclude' => true,
    'default' => '1.35',
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_isRTL'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_wrapTitleMonth'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_headerToolbar_start'] = [
    'exclude' => true,
    'inputType' => 'text',
    'default' => 'prev,next today',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_headerToolbar_center'] = [
    'exclude' => true,
    'default' => 'title',
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_headerToolbar_end'] = [
    'exclude' => true,
    'default' => 'month,agendaWeek,agendaDay',
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_tooltip_options'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'sql' => "text NULL",
    'eval' => ['tl_class' => 'long', 'useRawRequestData' => true],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['fullcal_options_additional'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'sql' => "text NULL",
    'eval' => ['tl_class' => 'long', 'useRawRequestData' => true],
];

