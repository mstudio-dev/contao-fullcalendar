<?php

namespace ContaoFullcalendar\Modules;

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2019 Leo Feyer
 *
 * PHP version 5
 * @copyright Martin Kozianka 2014-2019 <http://kozianka.de/>
 * @author    Martin Kozianka <http://kozianka.de/>
 * @package    contao-fullcalendar
 * @license    LGPL
 * @filesource
 */

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use Contao\CalendarModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ContaoFullcalendar\EventMapper;

#[AsFrontendModule(
    category: 'events',
    template: 'mod_fullcalendar'
)]
class ModuleFullCalendar extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $page = $this->get('contao.framework')->getAdapter(PageModel::class)->findByPk($GLOBALS['objPage']->id);

        $this->fullcal_viewButtons = ['month', 'agendaWeek', 'agendaDay'];

        $fullcalOptions = new \stdClass();
        $fullcalOptions->locale = $page->language;
        $fullcalOptions->firstDay = $model->cal_startDay;
        $fullcalOptions->aspectRatio = $model->fullcal_aspectRatio;
        $fullcalOptions->fixedWeekCount = ("1" === $model->fullcal_fixedWeekCount);
        $fullcalOptions->weekNumbers = ("1" === $model->fullcal_weekNumbers);

        if ($model->fullcal_contentHeight !== "") {
            $fullcalOptions->contentHeight = $model->fullcal_contentHeight;
        }

        if ("1" === $model->fullcal_isRTL) {
            $fullcalOptions->direction = "rtl";
        }

        $fullcalOptions->headerToolbar = new \stdClass();
        $fullcalOptions->headerToolbar->start = $model->fullcal_headerToolbar_start;
        $fullcalOptions->headerToolbar->center = $model->fullcal_headerToolbar_center;
        $fullcalOptions->headerToolbar->end = $model->fullcal_headerToolbar_end;

        $arrCalendarIds = array_map('intval', deserialize($model->cal_calendar));
        $arrCalendar = [];
        $collectionCal = CalendarModel::findMultipleByIds($arrCalendarIds);

        if ($collectionCal !== null) {
            foreach ($collectionCal as $objCal) {
                $arrCalendar[$objCal->fullcal_alias] = (object) [
                    'id' => $objCal->id,
                    'title' => $objCal->title,
                    'alias' => $objCal->fullcal_alias,
                    'color' => deserialize($objCal->fullcal_color),
                ];
            }
        }

        $template->assets->addJavaScript('bundles/contaofullcalendar/fullcalendar/main.min.js');
        $template->assets->addJavaScript('bundles/contaofullcalendar/fullcalendar/locales-all.min.js');
        $template->assets->addCss('bundles/contaofullcalendar/fullcalendar/main.min.css');
        $template->assets->addJavaScript('bundles/contaofullcalendar/fullcal.js');

        if (isset($model->fullcal_tooltip_options) && !ctype_space($model->fullcal_tooltip_options)) {
            $template->fullcalTooltipOptions = trim($model->fullcal_tooltip_options);

            $template->assets->addJavaScript('bundles/contaofullcalendar/popper/popper.min.js');
            $template->assets->addJavaScript('bundles/contaofullcalendar/tippy/tippy-bundle.umd.min.js');

            $template->assets->addCss('bundles/contaofullcalendar/tippy/themes/light-border.css');
            $template->assets->addCss('bundles/contaofullcalendar/tippy/themes/light.css');
            $template->assets->addCss('bundles/contaofullcalendar/tippy/themes/material.css');
            $template->assets->addCss('bundles/contaofullcalendar/tippy/themes/translucent.css');
        }

        if (isset($model->fullcal_options_additional) && !ctype_space($model->fullcal_options_additional)) {
            $template->fullcalOptionsAdditional = trim($model->fullcal_options_additional);
        }

        if ($model->fullcal_wrapTitleMonth === "1") {
            $template->appendStyle = join("\n", [
                ".fc-daygrid-event { display:block; white-space:normal; }",
                ".fc-daygrid-event > div { display:inline-block; }",
            ]);
        }

        $template->showMenu = true;
        $template->jsonEventSources = json_encode($this->getEventSources($arrCalendarIds, $model->fullcal_range), JSON_NUMERIC_CHECK);
        $template->fullcalOptions = json_encode($fullcalOptions, JSON_NUMERIC_CHECK);
        $template->arrCalendar = $arrCalendar;

        return $template->getResponse();
    }

    private function getEventSources(array $arrCalendarIds, string $range)
    {
        $arrCalendar = [];
        $collectionCal = CalendarModel::findMultipleByIds($arrCalendarIds);
        if ($collectionCal !== null) {
            foreach ($collectionCal as $calModel) {
                $arrColor = deserialize($calModel->fullcal_color);
                if (is_array($arrColor) && strlen($arrColor[0]) > 0) {
                    $calModel->fullcal_hexColor = '#' . $arrColor[0];
                }
                $arrCalendar[$calModel->id] = $calModel;
            }
        }

        // Time range
        $jsonEventSources = new \stdClass();
        $tsStart = strtotime('-' . str_replace("_", " ", $range), time());
        $tsEnd = strtotime('+' . str_replace("_", " ", $range), time());

        $events = \CalendarEventsModel::findPublishedByPids($arrCalendarIds, $tsStart, $tsEnd);

        if ($events === null) {
            return $jsonEventSources;
        }

        $eventList = [];
        while($events->next()) {
            $eventList[] = $events->row();
        }

        foreach ($eventList as $event) {
            $calModel = $arrCalendar[$event['pid']];
            $calAlias = $calModel->fullcal_alias;

            if (!isset($jsonEventSources->$calAlias)) {
                $eventSource = new \stdClass();
                $eventSource->id = $calAlias;
                $eventSource->hexColor = isset($calModel->fullcal_hexColor) ? $calModel->fullcal_hexColor : null;
                $eventSource->events = [];
                $jsonEventSources->$calAlias = $eventSource;
            } else {
                $eventSource = $jsonEventSources->$calAlias;
            }

            $newEvent = EventMapper::convert($event);

            $newEvent->calendarAlias = $calAlias;
            $newEvent->backgroundColor = isset($calModel->fullcal_hexColor) ? $calModel->fullcal_hexColor : null;
            $newEvent->className .= " " . $calAlias;

            $eventSource->events[] = $newEvent;
        }

        return $jsonEventSources;
    }
}
