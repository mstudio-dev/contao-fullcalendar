<?php

namespace ContaoFullcalendar\EventListener\Dca;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\ModuleModel;

class ModuleListener
{
    /**
     * @Callback(table="tl_module", target="config.onsubmit")
     */
    public function checkCalNoSpan(DataContainer $dc): void
    {
        $id = (int)$dc->id;
        $moduleObj = ModuleModel::findByPk($id);

        if ($moduleObj && $moduleObj->type === 'fullcalendar') {
            $moduleObj->cal_noSpan = '1';
            $moduleObj->save();
        }
    }
}
