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
use Avisota\Contao\Selectri\Model\Flat\SQLListDataGroupedConfig;
use Avisota\Contao\Selectri\Model\Flat\SQLListSelectAbleNode;
use Avisota\Contao\Selectri\Model\Tree\SQLAdjacencyTreeDataConfigWithItems;
use Contao\Database;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Model\Flat\SQLListData;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Widget;

/**
 * Class EventYearListData
 *
 * @package Avisota\Contao\Message\Element\News\DataContainer
 */
class EventYearListData extends AbstractData
{
    use DatabaseTrait;

    const SEARCH_ABLE = false;

    const BROWSE_ABLE = true;

    /**
     * EventYearListData constructor.
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
     * @param null $chunks
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function browseFrom($chunks = null)
    {
        $listData = new SQLListData(
            $this->getWidget(),
            $this->getDatabase(),
            $this->prepareListDataConfig($chunks)
        );

        list($eventYearLevels, $key) = $listData->browseFrom(null);

        $levels = new \ArrayIterator();
        while ($newsArchive = $eventYearLevels->current()) {
            $node = $newsArchive->getData();

            $node['_isSelectable'] = false;
            $node['_key']          = implode(
                '::',
                array_merge(
                    $chunks,
                    array('year', $node['year'])
                )
            );

            $listNode = new SQLListSelectAbleNode($listData, $node);

            $levels->append($listNode);

            $eventYearLevels->next();
        }

        return array($levels, implode('::', $chunks));
    }

    /**
     * Prepare the list grouped data configuration.
     *
     * @return SQLAdjacencyTreeDataConfigWithItems
     */
    protected function prepareListDataConfig($chunks)
    {
        $config = new SQLListDataGroupedConfig();

        $config->setTable('tl_calendar_events');
        $config->setKeyColumn('id');
        $config->setColumns('DATE_FORMAT(FROM_UNIXTIME(startDate), \'%%Y\') as year');
        $config->setConditionExpr('pid=' . $chunks[1]);
        $config->setGroupByParameter('year');
        $config->setLabelCallback($this->prepareLabelCallback($config));
        $config->setIconCallback($this->prepareIconCallback());

        return $config;
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
            'year'
        );

        $labelFormatter->setFields(array('year'));
        $labelFormatter->setFormat('%s');

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
            'prepareEventYearIconCallback'
        );
    }

    /**
     * Prepare news archive icon callback.
     *
     * @return string
     */
    public function prepareEventYearIconCallback()
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
        // The event year don´t get nodes.
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
        // The event year don´t filter nodes.
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isSearchable()
     */
    public function isSearchable()
    {
        return EventYearListData::SEARCH_ABLE;
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isBrowsable()
     */
    public function isBrowsable()
    {
        return EventYearListData::BROWSE_ABLE;
    }
}
