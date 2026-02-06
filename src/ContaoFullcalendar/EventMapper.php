<?php

namespace ContaoFullcalendar;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Dbafs;
use Contao\StringUtil;
use Contao\Template;
use ContaoFullcalendar\Dto\FullCalendarEventDto;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Node;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventMapper
{
    private static ?ContaoFramework $framework = null;
    private static ?SluggerInterface $slugger = null;

    public function __construct(ContaoFramework $framework, SluggerInterface $slugger)
    {
        self::$framework = $framework;
        self::$slugger = $slugger;
    }

    /**
     * Convert "Contao-Event-Array" to a DTO for fullcalendar.
     */
    public static function convert(array $event): FullCalendarEventDto
    {
        $cssClasses = array_map('trim', explode(' ', $event['class']));
        $cssClasses[] = 'jsonEvent';

        $newEvent = new FullCalendarEventDto();
        $newEvent->id = (int) $event['id'];
        $newEvent->pid = (int) $event['pid'];
        $newEvent->alias = $event['alias'];
        $newEvent->title = html_entity_decode((string) $event['title']);
        $newEvent->details = (array_key_exists("details", $event) && is_string($event['details'])) ? strip_tags($event['details']) : null;
        $newEvent->author = $event['author'];
        $newEvent->teaser = $event['teaser'];
        $newEvent->location = $event['location'];
        $newEvent->href = $event['href'];

        $begin = (int) $event['begin'];
        $end = (int) $event['end'];

        $dateBegin = date('Y-m-d', $begin);
        $dateEnd = date('Y-m-d', $end);
        $timeBegin = date('H:i', $begin);
        $timeEnd = date('H:i', $end);

        if ($event['fullcal_cat']) {
            $cssClasses[] = 'cat_' . self::$slugger->slug((string) $event['fullcal_cat'])->lower();
        }

        if ($event['addTime'] === '') {
            // No time
            $newEvent->start = $dateBegin;
            if ($dateBegin !== $dateEnd) {
                // Add one day for fullcalendar
                $newEvent->end = date('Y-m-d', strtotime('+1 day', $end));
                $cssClasses[] = 'days';
            } else {
                $cssClasses[] = 'oneDay';
            }
        } elseif ($begin === $end) {
            // Event with start time but no end time
            $newEvent->start = date('c', $begin);
            $cssClasses[] = 'oneDayTime';
        } elseif ($timeBegin === $timeEnd) {
            // Only a start time
            $newEvent->start = date('c', $begin);
            $newEvent->end = $dateEnd;
            $cssClasses[] = 'daysStart';
        } else {
            // Multiple days with start and end time
            $newEvent->start = date('c', $begin);
            $newEvent->end = date('c', $end);
            $cssClasses[] = 'daysTime';
        }

        /** @var Template $template */
        $template = self::$framework->createInstance(Template::class, ['fullcal_description']);
        $template->setData($event);

        $newEvent->description = $template->parse();
        $newEvent->className = implode(' ', $cssClasses);

        return $newEvent;
    }

    /**
     * Get CalendarEventsModel from VEvent.
     */
    public static function getCalendarEventsModel(Node $vevent, \Contao\Model $calObj, \DateTimeZone $objTimezone): CalendarEventsModel
    {
        $eData = static::serializeVevent($vevent);
        $objTimestamp = $vevent->DTSTAMP->getDateTime();
        $objStartDate = $vevent->DTSTART->getDateTime();
        $objEndDate = $vevent->DTEND->getDateTime();
        $objStartDate->setTimezone($objTimezone);
        $objEndDate->setTimezone($objTimezone);
        $objTimestamp->setTimezone($objTimezone);

        // Only if start AND end have a time, the event has a time
        $addTime = ($vevent->DTSTART->hasTime() && $vevent->DTEND->hasTime());

        $eventId = $eData['uid'] . '_' . $objStartDate->getTimestamp();
        $eventObject = CalendarEventsModel::findOneBy('fullcal_id', $eventId);

        if ($eventObject === null) {
            $isNew = true;
            $eventObject = new CalendarEventsModel();
        } else {
            $isNew = false;
            $eventObject->fullcal_flagNew = false;
        }

        $eventObject->fullcal_id = $eventId;
        $eventObject->fullcal_uid = $eData['uid'] ?? '';
        $eventObject->fullcal_desc = $eData['description'] ?? '';
        $eventObject->fullcal_cat = $eData['categories'] ?? '';
        $eventObject->teaser = $eData['description'] ?? '';
        $eventObject->title = $eData['summary'] ?? '';
        $eventObject->location = $eData['location'] ?? '';
        $eventObject->pid = $calObj->id;
        $eventObject->source = 'default';
        $eventObject->published = '1';
        $eventObject->addTime = $addTime ? '1' : '';
        $eventObject->tstamp = $objTimestamp->getTimestamp();
        $eventObject->startDate = $objStartDate->getTimestamp();
        $eventObject->endDate = $objEndDate->getTimestamp();
        $eventObject->startTime = $objStartDate->getTimestamp();
        $eventObject->endTime = $objEndDate->getTimestamp();

        if ($objStartDate->format('dmY') === $objEndDate->format('dmY')) {
            $eventObject->endDate = null;
        }

        if (!$addTime) {
            // Remove time info
            $eventObject->startDate = strtotime(date("Y-m-d", (int)$eventObject->startDate));
            $eventObject->startTime = $eventObject->startDate;

            $objIntervalOneDay = new \DateInterval('P1D');
            $objStartDate->add($objIntervalOneDay);

            if ($objStartDate->format('dmY') === $objEndDate->format('dmY')) {
                $eventObject->endDate = null;
                $eventObject->endTime = $eventObject->startDate;
            } else {
                // For multi-day events without time, the last day must be subtracted.
                $objEndDateSubbed = $objEndDate->sub($objIntervalOneDay);
                $eventObject->endDate = $objEndDateSubbed->getTimestamp();
                $eventObject->endTime = $objEndDateSubbed->getTimestamp();
            }
        }

        $eventObject->save();

        // After first save() because the id is necessary for alias generation
        self::generateAlias($eventObject);

        // Save the single event as ics files
        static::saveEventAsIcs($eventObject, $vevent);

        $eventObject->fullcal_flagNew = $isNew;
        return $eventObject;
    }

    /**
     * Get a flat array with the event infos.
     */
    public static function serializeVevent(Node $vevent): array
    {
        $values = [];
        $jsonObj = $vevent->jsonSerialize();

        foreach ($jsonObj[1] as $arrAttr) {
            if (count($arrAttr) === 4) {
                $key = $arrAttr[0];
                $values[$key] = $arrAttr[3];
            }
        }
        return $values;
    }

    private static function generateAlias(CalendarEventsModel $eventModel): void
    {
        $alias = self::$slugger->slug($eventModel->title)->lower();
        $eventModel->alias = $alias;
        $eventModel->save();
    }

    private static function saveEventAsIcs(CalendarEventsModel $eventModel, VEvent $vevent): void
    {
        $vcalendar = new VCalendar();
        $vcalendar->add($vevent);

        $fs = self::$framework->getAdapter(Dbafs::class);
        $folderPath = CalendarSyncService::ICS_FOLDER_PATH . '/' . $eventModel->getRelated('pid')->fullcal_alias;
        if (!$fs->has($folderPath)) {
            $fs->createFolder($folderPath);
        }
        $filePath = $folderPath . '/' . $eventModel->alias . '.ics';
        $fs->write($filePath, $vcalendar->serialize());
    }
}

    /**
     * Save one event in an ics file
     * @param CalendarEventsModel $eventObject
     * @param \Sabre\VObject\Node $vevent
     */
    private static function saveEventAsIcs(CalendarEventsModel $eventObject, Node $vevent)
    {
        // Generate a unique filename
        $strFile = CalendarSync::$icsFolder . $eventObject->alias . '.ics';

        // Create a calendar & add the event
        $cal = new VCalendar();
        $cal->add($vevent);

        // Save this calendar as an ics file
        $file = new File($strFile);
        $file->write($cal->serialize());
        $file->close();

        // Save the reference to the ics file in the event
        $eventObject->fullcal_ics = str_replace("web/", "", $strFile);
        $eventObject->save();
    }

    /* Generate alias for CalendarEventsModel
     * @param \CalendarEventsModel
     */
    private static function generateAlias(CalendarEventsModel $eventObj)
    {
        $strAlias = standardize(StringUtil::restoreBasicEntities($eventObj->title));

        $objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_calendar_events WHERE alias=?")
            ->execute($strAlias);

        if ($objAlias->numRows > 1) {
            $strAlias .= '-' . $eventObj->id;
        }
        $eventObj->alias = $strAlias;
        $eventObj->save();
    }
}
