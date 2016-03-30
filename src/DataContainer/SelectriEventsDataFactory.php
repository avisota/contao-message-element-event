<?php

/**
 * Avisota newsletter and mailing system
 * Copyright © 2016 Sven Baumann
 *
 * PHP version 5
 *
 * @copyright  way.vision 2016
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @package    avisota/contao-message-element-event
 * @license    LGPL-3.0+
 * @filesource
 */

namespace Avisota\Contao\Message\Element\Event\DataContainer;

use Hofff\Contao\Selectri\Model\Flat\SQLListDataFactory;
use Hofff\Contao\Selectri\Widget;

/**
 * Class SelectriEventsDataFactory
 */
class SelectriEventsDataFactory extends SQLListDataFactory
{
    /**
     * @param Widget $widget
     *
     * @return SelectriEventsDataFactory A new data instance
     */
    public function createData(Widget $widget = null)
    {
        $data = new SelectriEventsData();
        $data->setWidget($widget);
        return $data;
    }
}
