<?php

namespace ContaoFullcalendar\InsertTag;

use Contao\CalendarModel;
use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\InsertTag\InsertTagParserInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use ContaoFullcalendar\Service\CalendarSyncService;
use Symfony\Component\HttpFoundation\RequestStack;

class FullCalendarInsertTagParser implements InsertTagParserInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly Dbafs $dbafs
    ) {
    }

    public function supports(string $tag): bool
    {
        return str_starts_with($tag, 'fullcal_');
    }

    public function parse(string $tag, bool $cache, string $flags, array $cacheValue, ?array $subtags): string|false
    {
        $tagValues = explode('::', $tag);
        $tagName = array_shift($tagValues);

        if (!$this->supports($tagName)) {
            return false;
        }

        if (count($tagValues) === 0) {
            return sprintf('{{%s}} Error: No params given', $tag);
        }

        $this->framework->initialize();
        $calendarModelAdapter = $this->framework->getAdapter(CalendarModel::class);

        if (is_numeric($tagValues[0])) {
            $calendar = $calendarModelAdapter->findByPk($tagValues[0]);
        } else {
            $calendar = $calendarModelAdapter->findOneBy('fullcal_alias', $tagValues[0]);
        }

        if ($calendar === null) {
            return sprintf('{{%s}} Error: No calendar found', $tag);
        }

        if ($tagName === 'fullcal_alias') {
            return $calendar->fullcal_alias;
        }

        // Find the latest .ics file for the given calendar alias
        $folderPath = CalendarSyncService::ICS_FOLDER_PATH . '/' . $calendar->fullcal_alias;
        $files = $this->dbafs->findFiles($folderPath, ['order' => 'mtime DESC', 'limit' => 1]);

        if (empty($files)) {
            return sprintf('{{%s}} Error: No ICS file found for calendar', $tag);
        }
        
        $fileModel = $this->dbafs->find($files[0]);

        if (null === $fileModel) {
             return sprintf('{{%s}} Error: No ICS file found for calendar', $tag);
        }

        $request = $this->requestStack->getCurrentRequest();
        $calUrl = $request ? $request->getSchemeAndHttpHost() . '/' . $fileModel->path : $fileModel->path;

        if ($tagName === 'fullcal_url') {
            return $calUrl;
        }

        if ($tagName === 'fullcal_link') {
            return sprintf(
                '<a href="%s" title="%s [%s]">%s</a>',
                $calUrl,
                $calendar->title,
                $calUrl,
                ($tagValues[1] ?? $calendar->title)
            );
        }

        return false;
    }
}
