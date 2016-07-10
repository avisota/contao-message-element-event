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

use Avisota\Contao\Selectri\DataContainer\DatabaseTrait;
use Contao\Database;
use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataFactory;
use Hofff\Contao\Selectri\Widget;

/**
 * Class EventListDataFactory
 *
 * @package Avisota\Contao\Message\Element\Event\DataContainer
 */
class EventListDataFactory implements DataFactory
{
    use DatabaseTrait;

    /**
     * EventListDataFactory constructor.
     */
    public function __construct()
    {
        $this->setDatabase(Database::getInstance());
    }

    /**
     * @see Widget
     *
     * @param mixed $params Configuration parameters (usally the eval array of
     *                      the DCA field the widget using this factory)
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setParameters($params)
    {
        // TODO: Implement setParameters() method.
    }

    /**
     * @see Widget
     *
     * @param Widget $widget The widget the created data instance will belong to
     *
     * @return Data A new data instance
     */
    public function createData(Widget $widget = null)
    {
        return new EventController($widget, $this->getDatabase());
    }
}
