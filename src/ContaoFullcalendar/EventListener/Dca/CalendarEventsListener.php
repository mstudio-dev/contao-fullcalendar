<?php

namespace ContaoFullcalendar\EventListener\Dca;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Input;

class CalendarEventsListener
{
    /**
     * @Callback(table="tl_calendar_events", target="config.onload")
     */
    public function adjustDca(): void
    {
        if ($calObj = \CalendarModel::findByPk(\Input::get('id'))) {
            if ($calObj->fullcal_type !== '') {
                $GLOBALS['TL_DCA']['tl_calendar_events']['list']['global_operations']['fullcal'] = [
                    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['fullcal_syn'],
                    'href' => 'key=fullcal',
                    'class' => 'header_sync',
                    'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="s"',
                ];
            }
        }

        if ('edit' === Input::get('act')) {
            $eventObj = CalendarEventsModel::findByPk(Input::get('id'));
            if ($eventObj && $eventObj->fullcal_uid !== '') {
                $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default'] =
                    str_replace(
                        [
                            '{details_legend},',
                            '{title_legend},title,alias,author;',
                            '{date_legend},addTime,startDate,endDate;',
                            'location,',
                            '{recurring_legend},recurring;',
                            '{publish_legend},published,start,stop',
                        ],
                        ['{details_legend},fullcal_detailViewer,', ''],
                        $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default']
                    );
            }
        }
    }

    /**
     * @Callback(table="tl_calendar_events", target="list.label.label")
     */
    public function listEvents(array $row): string
    {
        $this->framework->initialize();
        $dateAdapter = $this->framework->getAdapter(\Contao\Date::class);

        $time = '';
        $date = '';

        if ($row['addTime']) {
            $time = ' (' . $dateAdapter->parse('H:i', $row['startTime']);

            if ($row['endTime'] && $row['startTime'] !== $row['endTime']) {
                $time .= ' - ' . $dateAdapter->parse('H:i', $row['endTime']);
            }

            $time .= ')';
        }

        if ($row['startDate'] === $row['endDate'] || !$row['endDate']) {
            $date = $dateAdapter->parse('d.m.Y', $row['startDate']);
        } else {
            $date = $dateAdapter->parse('d.m.Y', $row['startDate']) . ' - ' . $dateAdapter->parse('d.m.Y', $row['endDate']);
        }

        return '<div>' . $row['title'] . ' <span style="color:#999;padding-left:3px">[' . $date . $time . ']</span></div>';
    }
}
}
