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
use Avisota\Contao\Selectri\Model\Tree\SQLAdjacencyTreeDataConfigWithItems;
use Contao\BackendUser;
use Contao\Database;
use Contao\Image;
use Contao\System;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Model\Flat\SQLListData;
use Hofff\Contao\Selectri\Model\Flat\SQLListNode;
use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Util\Icons;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Widget;

/**
 * Class EventListData
 *
 * @package Avisota\Contao\Message\Element\News\DataContainer
 */
class EventListData extends AbstractData
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

        list($eventLevels, $key) = $listData->browseFrom(null);

        $levels = new \ArrayIterator();
        while ($newsArchive = $eventLevels->current()) {
            $node = $newsArchive->getData();

            $node['_key'] = implode(
                '::',
                array('tl_calendar_events', $node['id'])
            );

            $listNode = new SQLListNode($listData, $node);

            $levels->append($listNode);

            $eventLevels->next();
        }

        return array($levels, implode('::', $chunks));
    }

    /**
     * prepare the list data configuration.
     *
     * @return SQLAdjacencyTreeDataConfigWithItems
     */
    protected function prepareListDataConfig($chunks = array())
    {
        $config = new SQLListDataGroupedConfig();

        $config->setTable('tl_calendar_events');
        $config->setKeyColumn('id');
        $config->setColumns($this->getColumns());
        $config->setOrderByExpr('startTime');
        $config->setLabelCallback($this->prepareLabelCallback($config));
        $config->setIconCallback($this->prepareIconCallback());
        $config->setContentCallback($this->prepareContentCallback());

        if (count($chunks) > 0) {
            $config->setConditionExpr($this->prepareConditionExpression($chunks));
        }

        return $config;
    }


    /**
     * Prepare the condition expression.
     *
     * @param $chunks
     *
     * @return string
     */
    protected function prepareConditionExpression($chunks)
    {
        $expression = 'pid=' . $chunks[1];

        $date = new \DateTime();

        $date->modify($chunks[3] . '-' . $chunks[5] . '-01');
        $date->modify('yesterday');
        $expression .= ' AND startDate > ' . $date->getTimestamp();

        $date->modify($chunks[3] . '-' . ($chunks[5] + 1) . '-01');
        $date->modify('yesterday');
        $expression .= ' AND startDate < ' . $date->getTimestamp();

        return $expression;
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
            'id'
        );

        $labelFormatter->setFields(
            array('title', 'id')
        );
        $labelFormatter->setFormat('%s (ID %s)');

        return $labelFormatter->getCallback();
    }

    /**
     * Prepare the icon callback.
     *
     * @return array
     */
    public function prepareIconCallback()
    {
        return array(
            __CLASS__,
            'prepareEventIconCallback'
        );
    }

    /**
     * Prepare article icon callback.
     *
     * @return string
     */
    public function prepareEventIconCallback()
    {
        $user = BackendUser::getInstance();

        return sprintf(
            'system/themes/%s/images/%s',
            $user->backendTheme,
            Icons::getTableIcon('tl_article')
        );
    }

    /**
     * Prepare content callback.
     *
     * @return array
     */
    protected function prepareContentCallback()
    {
        return array(
            __CLASS__,
            'getContent'
        );
    }

    /**
     * Get the content callback listener.
     *
     * @param Node $node
     *
     * @return string
     */
    public function getContent(Node $node)
    {
        return sprintf(
            "<div style=\" float: right; margin-right: 64px; margin-top: 3px;\">%s</div>",
            self::prepareButtons(explode('::', $node->getKey())[1])
        );
    }

    /**
     * Prepare buttons.
     *
     * @param $newsId
     *
     * @return string
     */
    protected function prepareButtons($newsId)
    {
        global $container;

        System::loadLanguageFile('tl_calendar_events');

        $translator = $container['translator'];

        $buttons = self::getModalEditButton($newsId, $translator);
        $buttons .= self::getModalShowButton($newsId, $translator);

        return $buttons;
    }

    /**
     * Get modal edit button.
     *
     * @param $newsId
     *
     * @return string
     */
    protected function getModalEditButton($newsId, $translator)
    {
        $urlParams = array(
            array(
                'name'  => 'id',
                'value' => $newsId
            ),
            array(
                'name'  => 'popup',
                'value' => 1
            ),
        );

        $label = $translator->translate('edit.1', 'tl_calendar_events');

        return '<a ' .
               'href="' . self::getBackendUrl($urlParams) . '" ' .
               'title="' . self::getTitle($label, $newsId) . '" ' .
               'onclick="' . self::getOnClickModal($label, $newsId) . '" ' .
               'class="edit">' .
               Image::getHtml('edit.gif', $translator->translate('edit.0', 'tl_calendar_events')) .
               '</a> ';
    }

    /**
     * Get modal show button.
     *
     * @param $newsId
     *
     * @return string
     */
    protected function getModalShowButton($newsId, $translator)
    {
        $urlParams = array(
            array(
                'name'  => 'act',
                'value' => 'show'
            ),
            array(
                'name'  => 'table',
                'value' => 'tl_calendar_events'
            ),
            array(
                'name'  => 'id',
                'value' => $newsId
            ),
            array(
                'name'  => 'popup',
                'value' => 1
            ),
        );

        $label = $translator->translate('show.1', 'tl_calendar_events');

        return '<a ' .
               'href="' . self::getBackendUrl($urlParams) . '" ' .
               'title="' . self::getTitle($label, $newsId) . '" ' .
               'onclick="' . self::getOnClickModal($label, $newsId) . '" ' .
               'class="edit">' .
               Image::getHtml('show.gif', $translator->translate('show.0', 'tl_calendar_events')) .
               '</a> ';
    }

    /**
     * Get backend url.
     *
     * @param array $params
     *
     * @return string
     */
    protected function getBackendUrl(array $params)
    {
        $urlBuilder = new UrlBuilder();
        $urlBuilder->setPath('contao/main.php')
            ->setQueryParameter('do', 'calendar')
            ->setQueryParameter('table', 'tl_content');


        foreach ($params as $param) {
            $urlBuilder->setQueryParameter($param['name'], $param['value']);
        }

        $urlBuilder->setQueryParameter('rt', REQUEST_TOKEN);
        $urlBuilder->setQueryParameter('ref', TL_REFERER_ID);

        return $urlBuilder->getUrl();
    }

    /**
     * Get title.
     *
     * @param $label
     * @param $newsId
     *
     * @return string
     */
    protected function getTitle($label, $newsId)
    {
        return specialchars(sprintf($label, $newsId));
    }

    /**
     * Get on click for modal.
     *
     * @param $label
     * @param $newsId
     *
     * @return string
     */
    protected function getOnClickModal($label, $newsId)
    {
        return 'Backend.openModalIframe({\'width\':768,\'title\':\'' .
               specialchars(str_replace("'", "\\'", sprintf($label, $newsId))) .
               '\',\'url\':this.href});return false';
    }

    /**
     * Get the page columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $database = $this->getDatabase();

        $properties = array();
        foreach ($database->listFields('tl_calendar_events') as $property) {
            if (!array_key_exists('origtype', $property)) {
                continue;
            }

            array_push($properties, $property['name']);
        }

        return $properties;
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
     */
    public function getNodes(array $keys, $selectableOnly = true)
    {
        $listData = new SQLListData(
            $this->getWidget(),
            $this->getDatabase(),
            $this->prepareListDataConfig()
        );

        $eventNodes = $listData->getNodes($keys, $selectableOnly);
        if ($eventNodes instanceof \EmptyIterator) {
            return $eventNodes;
        }

        $nodes = new \ArrayIterator();
        /** @var SQLListNode $current */
        while ($current = $eventNodes->current()) {
            $node = $current->getData();

            $node['_key'] = 'tl_calendar_events::' . $node['_key'];

            $listNode = new SQLListNode($listData, $node);

            $nodes->append($listNode);

            $eventNodes->next();
        }

        return $nodes;
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
        $listData = new SQLListData(
            $this->getWidget(),
            $this->getDatabase(),
            $this->prepareListDataConfig()
        );

        $filterKeys = array();
        if (count($keys) > 0) {
            foreach ($keys as $key) {
                array_push($filterKeys, explode('::', $key)[1]);
            }
        }

        return $listData->filter($filterKeys);
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isSearchable()
     */
    public function isSearchable()
    {
        return EventListData::SEARCH_ABLE;
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isBrowsable()
     */
    public function isBrowsable()
    {
        return EventListData::BROWSE_ABLE;
    }
}
