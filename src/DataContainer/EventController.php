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
use Avisota\Contao\Selectri\Widget;
use Contao\Database;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Model\Flat\SQLListData;
use Hofff\Contao\Selectri\Model\Flat\SQLListDataConfig;
use Iterator;

/**
 * Class EventController
 *
 * @package Avisota\Contao\Message\Element\Event\DataContainer
 */
class EventController extends SQLListData
{
    use DatabaseTrait;

    /**
     * EventController constructor.
     *
     * @param Widget   $widget
     * @param Database $database
     */
    public function __construct(Widget $widget, Database $database)
    {
        parent::__construct($widget, $database, $this->prepareConfig());
    }

    /**
     * Prepare empty configuration.
     *
     * @return SQLListDataConfig
     */
    protected function prepareConfig()
    {
        $config = new SQLListDataConfig();

        return $config;
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::browseFrom()
     *
     * @param null $key
     *
     * @return array
     */
    public function browseFrom($key = null)
    {
        $chunks = explode('::', $key);

        $selectKey = $key;
        switch (count($chunks)) {
            case 2:
                $selectKey = $chunks;
                $data      = new EventYearListData($this->getWidget(), Database::getInstance());
                break;

            case 4:
                $selectKey = $chunks;
                $data      = new EventMonthListData($this->getWidget(), Database::getInstance());
                break;

            case 6:
                $selectKey = $chunks;
                $data      = new EventListData($this->getWidget(), Database::getInstance());
                break;

            default:
                $data = new EventCalendarListData($this->getWidget(), Database::getInstance());
                break;
        }

        $return = $data->browseFrom($selectKey);

        return $return;
    }

    /**
     * @throws SelectriException If this data instance is not configured correctly
     * @return void
     */
    public function validate()
    {
        // Do nothing, is ever valid.
    }

    /**
     * Returns an iterator over nodes identified by the given primary
     * keys.
     *
     * The returned nodes should NOT be traversed recursivly through the node's
     * getChildrenIterator method.
     *
     * @param         array <string> $keys An array of primary key values in their
     *                      string representation
     * @param boolean $selectableOnly
     *
     * @return Iterator<Node> An iterator over the nodes identified by
     *        the given primary keys
     */
    public function getNodes(array $keys, $selectableOnly = true)
    {
        $newsList = new EventListData($this->getWidget(), Database::getInstance());

        return $newsList->getNodes($keys, $selectableOnly);
    }

    /**
     * Filters the given primary keys for values identifing only existing
     * records.
     *
     * @param array <string> $keys An array of primary key values in their
     *              string representation
     *
     * @return array<string> The input array with all invalid values removed
     */
    public function filter(array $keys)
    {
        $newsList = new EventListData($this->getWidget()->getData(), Database::getInstance());

        return $newsList->filter($keys);
    }

    public function isSearchable()
    {
        return false;
    }
}
