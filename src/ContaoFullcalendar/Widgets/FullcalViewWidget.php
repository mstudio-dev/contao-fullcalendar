<?php

namespace ContaoFullcalendar\Widgets;

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

namespace ContaoFullcalendar\Widgets;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Intl\ContaoIntlFormatter;
use Contao\CoreBundle\Widget\AbstractWidget;
use Contao\System;
use ContaoFullcalendar\EventListener\Dca\CalendarEventsListener;
use Symfony\Contracts\Translation\TranslatorInterface;

class FullcalViewWidget extends AbstractWidget
{
    protected $strTemplate = 'be_widget';

    public function __construct(
        array $attributes,
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly ContaoIntlFormatter $intlFormatter,
        private readonly CalendarEventsListener $calendarEventsListener
    ) {
        parent::__construct($attributes);
    }

    public function generate(): string
    {
        $event = $this->activeRecord;
        if (null === $event) {
            return '';
        }

        $lang = $this->translator->load('tl_calendar_events');

        $rows = [];
        $rows[$lang['title'][0]] = $event->title;
        $rows[$lang['fullcal_time'][0]] = $this->calendarEventsListener->listEvents($event->row());
        $rows[$lang['tstamp'][0]] = $this->intlFormatter->formatDateTime($event->tstamp);
        $rows[$lang['location'][0]] = $event->location;
        $rows[$lang['alias'][0]] = $event->alias;
        $rows[$lang['fullcal_uid'][0]] = $event->fullcal_uid;
        $rows[$lang['fullcal_desc'][0]] = $event->fullcal_desc;

        $this->strTemplate = 'fullcal_view_widget';
        $this->Template = new \BackendTemplate($this->strTemplate);
        $this->Template->rows = $rows;

        return parent::generate();
    }

    /**
     * This is an abstract method in the parent class and must be implemented.
     */
    public function parse($attributes = null): string
    {
        return parent::parse($attributes);
    }
}
