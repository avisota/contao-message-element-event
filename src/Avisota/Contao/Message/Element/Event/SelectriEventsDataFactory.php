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

namespace Avisota\Contao\Message\Element\Event;

/**
 * Class SelectriEventsDataFactory
 */
class SelectriEventsDataFactory extends \SelectriAbstractDataFactory
{
    /**
     * @return SelectriData A new data instance
     */
    public function createData()
    {
        $data = new SelectriEventsData();
        $data->setWidget($this->getWidget());
        return $data;
    }
}
