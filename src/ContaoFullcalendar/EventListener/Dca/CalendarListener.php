<?php

namespace ContaoFullcalendar\EventListener\Dca;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Image;
use Doctrine\DBAL\Connection;
use Exception;

class CalendarListener
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Callback(table="tl_calendar", target="list.label.label")
     */
    public function labelWithColor(array $row, string $label): string
    {
        $arrColor = deserialize($row['fullcal_color']);
        $strColor = sprintf('<span class="fullcal_color" style="background-color:#%s;">&nbsp;&nbsp;&nbsp;</span> ', $arrColor[0]);
        return $strColor . $label;
    }

    /**
     * @Callback(table="tl_calendar", target="list.operations.fullcal.button")
     */
    public function btnCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return ($row['fullcal_type'] !== '') ? '<a href="' . $href . '&amp;id=' . $row['id'] . '" title="' . htmlspecialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : '';
    }

    /**
     * @Callback(table="tl_calendar", target="fields.fullcal_alias.save")
     * @throws Exception
     */
    public function generateAlias(string $varValue, DataContainer $dc): string
    {
        $autoAlias = false;

        if ($varValue === '') {
            $autoAlias = true;
            $varValue = standardize($dc->activeRecord->title);
        }

        $stmt = $this->connection->executeQuery('SELECT id FROM tl_calendar WHERE fullcal_alias = ?', [$varValue]);
        $objAlias = $stmt->fetchAll();

        if (count($objAlias) > 1 && !$autoAlias) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        if (count($objAlias) && $autoAlias) {
            $varValue .= '-' . $dc->id;
        }

        return $varValue;
    }
}
