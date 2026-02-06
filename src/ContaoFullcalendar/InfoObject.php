<?php

namespace ContaoFullcalendar;

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2019 Leo Feyer
 *
 * PHP version 5
 * @copyright Martin Kozianka 2014-2019 <http://kozianka.de/>
 * @author    Martin Kozianka <http://kozianka.de/>
 * @package   contao-fullcalendar
 * @license   LGPL
 * @filesource
 */

namespace ContaoFullcalendar;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;

class InfoObject
{
    private ?\Exception $exception = null;
    private string $type = 'TL_INFO';
    private int $new = 0;
    private int $updated = 0;
    private int $deleted = 0;

    public function __construct(private readonly CalendarModel $calendar)
    {
    }

    public function add(CalendarEventsModel $event): void
    {
        if ($event->fullcal_flagNew) {
            $this->new++;
        } else {
            $this->updated++;
        }
    }

    public function setDeleted(int $deletedCount): void
    {
        $this->deleted = $deletedCount;
    }

    public function getMessage(): string
    {
        if ($this->exception !== null) {
            return $this->exception->getMessage();
        }

        return sprintf(
            'Kalender <strong>%s</strong>: %s Events eingefügt, %s Events aktualisiert, %s Events gelöscht',
            $this->calendar->title,
            $this->new,
            $this->updated,
            $this->deleted
        );
    }

    public function setException(\Exception $e): void
    {
        $this->type = 'TL_ERROR';
        $this->exception = $e;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
