<?php

namespace ContaoFullcalendar\Service;

use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Dbafs;
use Contao\File;
use Contao\Folder;
use ContaoFullcalendar\EventMapper;
use ContaoFullcalendar\InfoObject;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Reader;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalendarSyncService
{
    public const ICS_FOLDER_PATH = 'share/ics-cal';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient,
        private readonly string $projectDir,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function syncAll(): void
    {
        $this->framework->initialize();
        $collection = $this->framework->getAdapter(CalendarModel::class)->findAll([
            'column' => ["fullcal_type != ''"],
        ]);

        if (null === $collection) {
            return;
        }

        foreach ($collection as $calendar) {
            $infoObj = $this->updateCalendar($calendar);
            $this->logger?->info(strip_tags($infoObj->getMessage()));
        }
    }

    public function clearIcsFolder(): void
    {
        $this->framework->initialize();
        $folder = new Folder(self::ICS_FOLDER_PATH);
        $folder->purge();
        $this->logger?->info('Purged folder ' . self::ICS_FOLDER_PATH);
        $this->syncAll();
    }

    public function updateCalendar(CalendarModel $calendar): InfoObject
    {
        $infoObj = new InfoObject($calendar);

        try {
            $vcalContent = $this->getVCalendarContent($calendar);
        } catch (\Exception $e) {
            $infoObj->setException($e);
            return $infoObj;
        }

        $range = str_replace('_', ' ', $calendar->fullcal_range);
        $dateTimeStart = new \DateTime('-' . $range);
        $dateTimeEnd = new \DateTime('+' . $range);

        $vcalendar = Reader::read($vcalContent);

        // Save local version of the calendar
        $fs = $this->framework->getAdapter(Dbafs::class);
        $folderPath = self::ICS_FOLDER_PATH . '/' . $calendar->fullcal_alias;
        if (!$fs->has($folderPath)) {
            $fs->createFolder($folderPath);
        }
        $filePath = $folderPath . '/' . time() . '.ics';
        $fs->write($filePath, $vcalContent);

        $vcalendar = $vcalendar->expand($dateTimeStart, $dateTimeEnd);
        $timezone = new \DateTimeZone($this->framework->getAdapter('Contao\Config')->get('timeZone'));

        $eventIds = [];
        if ($vcalendar->VEVENT) {
            foreach ($vcalendar->VEVENT as $vevent) {
                $eventModel = EventMapper::getCalendarEventsModel($vevent, $calendar, $timezone);
                $eventIds[] = (int)$eventModel->id;
                $infoObj->add($eventModel);
            }
        }

        // Delete events that were not found
        $qb = $this->connection->createQueryBuilder();
        $qb->delete('tl_calendar_events')
            ->where('pid = :pid')
            ->andWhere("fullcal_id != ''")
            ->setParameter('pid', $calendar->id);

        if (!empty($eventIds)) {
            $qb->andWhere($qb->expr()->notIn('id', $eventIds));
        }

        $affectedRows = $qb->executeStatement();
        $infoObj->setDeleted($affectedRows);

        return $infoObj;
    }

    private function getVCalendarContent(CalendarModel $calendar): string
    {
        if ('public_ics' === $calendar->fullcal_type) {
            $response = $this->httpClient->request('GET', $calendar->fullcal_ics);
        } elseif ('webdav' === $calendar->fullcal_type) {
            $response = $this->httpClient->request('GET', $calendar->fullcal_baseUri . $calendar->fullcal_path, [
                'auth_basic' => [$calendar->fullcal_username, $calendar->fullcal_password],
                'verify_peer' => false, // Equivalent to CURLOPT_SSL_VERIFYPEER => 0
                'verify_host' => false, // Equivalent to CURLOPT_SSL_VERIFYHOST => 0
            ]);
        } else {
            throw new \Exception('Unknown sync type ' . $calendar->fullcal_type);
        }

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Failed to download calendar from ' . $response->getInfo('url') . ' (Status: ' . $response->getStatusCode() . ')');
        }

        $content = $response->getContent();

        if (empty($content)) {
            throw new \Exception('Downloaded calendar content is empty.');
        }

        return $content;
    }
}
