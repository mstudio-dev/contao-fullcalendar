<?php

namespace ContaoFullcalendar\Controller\Backend;

use Contao\Backend;
use Contao\CalendarModel;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use ContaoFullcalendar\Service\CalendarSyncService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/contao/fullcalendar", name=SyncController::class, defaults={"_scope" = "backend"})
 */
class SyncController extends Backend
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly CalendarSyncService $calendarSyncService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security
    ) {
    }

    /**
     * @Route("/sync/{id}", name="_sync", requirements={"id"="\d+"})
     */
    public function syncAction(Request $request, int $id): Response
    {
        $this->framework->initialize();

        if (!$this->security->isGranted('contao_user.modules')) {
            throw new AccessDeniedException('Not enough permissions to sync calendars.');
        }

        $calendar = $this->framework->getAdapter(CalendarModel::class)->findByPk($id);

        if ($calendar) {
            $infoObj = $this->calendarSyncService->updateCalendar($calendar);
            $this->framework->getAdapter(Message::class)->add($infoObj->getMessage(), $infoObj->getType());
        }

        return $this->redirect($this->getReferer($request));
    }

    private function getReferer(Request $request): string
    {
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, 'do=calendar')) {
            // Remove the "key" parameter from the referer
            return preg_replace('/&(amp;)?key=[^&]*/', '', $referer);
        }
        return $this->urlGenerator->generate('contao_backend');
    }
}
