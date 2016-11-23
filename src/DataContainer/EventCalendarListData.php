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
use Avisota\Contao\Selectri\Model\Flat\SQLListSelectAbleNode;
use Contao\BackendUser;
use Contao\Database;
use ContaoCommunityAlliance\DcGeneral\Data\DefaultDataProvider;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Model\Flat\SQLListData;
use Hofff\Contao\Selectri\Model\Flat\SQLListDataConfig;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Widget;
use Iterator;

/**
 * Class EventCalendarListData
 *
 * @package Avisota\Contao\Message\Element\Event\DataContainer
 */
class EventCalendarListData extends AbstractData
{
    use DatabaseTrait;

    const SEARCH_ABLE = false;

    const BROWSE_ABLE = true;

    /**
     * EventCalendarListData constructor.
     *
     * @param Widget   $widget
     *
     * @param Database $database
     */
    public function __construct(Widget $widget, Database $database)
    {
        parent::__construct($widget);
        $this->setDatabase($database);
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::browseFrom()
     *
     * @param null $key
     *
     * @return array
     *
     * @throws SelectriException
     */
    public function browseFrom($key = null)
    {
        $listData = new SQLListData(
            $this->getWidget(),
            $this->getDatabase(),
            $this->prepareListDataConfig()
        );

        list($eventCalendarLevels, $key) = $listData->browseFrom($key);

        $levels = new \ArrayIterator();
        while ($eventCalendar = $eventCalendarLevels->current()) {
            $node = $eventCalendar->getData();

            if ($this->isEmptyCalendar($node)) {
                $eventCalendarLevels->next();

                continue;
            }

            $node['_isSelectable'] = false;
            $node['_key']          = 'tl_calendar::' . $node['_key'];

            $listNode = new SQLListSelectAbleNode($listData, $node);

            $levels->append($listNode);

            $eventCalendarLevels->next();
        }

        return array($levels, $key);
    }

    /**
     * check if calendar is empty.
     *
     * @param $node
     *
     * @return bool
     */
    protected function isEmptyCalendar($node)
    {
        $dataProvider = new DefaultDataProvider();
        $dataProvider->setBaseConfig(
            array(
                'source' => 'tl_calendar_events'
            )
        );

        $count = $dataProvider->getCount(
            $dataProvider->getEmptyConfig()->setFilter(
                array(
                    array(
                        'property'  => 'pid',
                        'value'     => $node['id'],
                        'operation' => '='
                    )
                )
            )
        );

        if (intval($count) <= 0) {
            return true;
        }

        return false;
    }

    /**
     * Prepare the list data configuration.
     *
     * @return SQLListDataConfig
     */
    protected function prepareListDataConfig()
    {
        $user   = BackendUser::getInstance();
        $config = new SQLListDataConfig();

        $config->setTable('tl_calendar');
        $config->setKeyColumn('id');
        $config->addColumns($this->getColumns());
        // Todo is search column must be configured
        $config->addSearchColumns('title');
        $config->setOrderByExpr('title');
        $config->setLabelCallback($this->prepareLabelCallback($config));
        $config->setIconCallback($this->prepareIconCallback());

        if (!$user->isAdmin
            && count($user->calendars) > 0
        ) {
            $config->setConditionExpr('id IN (' . implode(', ', $user->calendars) . ')');
        }

        return $config;
    }

    /**
     * Get the calendar columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $database = $this->getDatabase();

        $properties = array();
        foreach ($database->listFields('tl_calendar') as $property) {
            if (!array_key_exists('origtype', $property)) {
                continue;
            }

            array_push($properties, $property['name']);
        }

        return $properties;
    }

    /**
     * Prepare the label callback.
     *
     * @param $config
     *
     * @return callable
     */
    protected function prepareLabelCallback($config)
    {
        $labelFormatter = SQLUtil::createLabelFormatter(
            $this->getDatabase(),
            $config->getTable(),
            $config->getKeyColumn()
        );

        return $labelFormatter->getCallback();
    }

    /**
     * Prepare the icon callback
     *
     * @return array
     */
    public function prepareIconCallback()
    {
        return array(
            __CLASS__,
            'prepareCalendarIconCallback'
        );
    }

    /**
     * Get calendar table icon callback.
     *
     * @return string
     */
    public function prepareCalendarIconCallback()
    {
        return 'system/modules/calendar/assets/icon.gif';
    }

    /**
     * @throws SelectriException If this data instance is not configured correctly
     *
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNodes(array $keys, $selectableOnly = true)
    {
        // The calendar don´t get nodes.
    }

    /**
     * Filters the given primary keys for values identifing only existing
     * records.
     *
     * @param array <string> $keys An array of primary key values in their
     *              string representation
     *
     * @return array<string> The input array with all invalid values removed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function filter(array $keys)
    {
        // The calendar don´t filter nodes.
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isSearchable()
     */
    public function isSearchable()
    {
        return EventCalendarListData::SEARCH_ABLE;
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isBrowsable()
     */
    public function isBrowsable()
    {
        return EventCalendarListData::BROWSE_ABLE;
    }
}
