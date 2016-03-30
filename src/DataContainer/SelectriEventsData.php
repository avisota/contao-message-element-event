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

use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Util\SearchUtil;
use Hofff\Contao\Selectri\Widget;
use SelectriWidget;

/**
 * Class SelectriEventsData
 */
class SelectriEventsData extends AbstractData
{
    /**
     * @var Widget
     */
    protected $widget;

    /**
     * @return Widget The widget this data belongs to
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * @param Widget $widget
     *
     * @return static
     */
    public function setWidget($widget)
    {
        $this->widget = $widget;
        return $this;
    }

    /**
     * Filters the given primary keys for values identifing only existing
     * records.
     *
     * @param array <string> $selection An array of primary key values in their
     *              string representation
     *
     * @return array<string> The input array with all invalid values removed
     */
    public function filter(array $keys)
    {
        return array_keys(iterator_to_array($this->getNodes($keys), true));
    }

    /**
     * Returns an iterator over selected nodes identified by the given primary
     * keys.
     *
     * The returned nodes should NOT be traversed recursivly through the node's
     * getChildrenIterator method.
     *
     * @param array <string> $selection An array of primary key values in their
     *              string representation
     *
     * @return \Iterator<SelectriNode> An iterator over the nodes identified by
     *        the given primary keys
     */
    public function getSelectionIterator(array $selection)
    {
        if (!$selection) {
            return new \EmptyIterator();
        }

        $map = array();

        foreach ($selection as $idWithTimestamp) {
            list($id, $timestamp) = explode('@', $idWithTimestamp);

            if (isset($map[$id])) {
                $map[$id][] = $timestamp;
            } else {
                $map[$id] = array($timestamp);
            }
        }

        $ids   = array_keys($map);
        $inSet = rtrim(str_repeat('?,', count($ids)), ',');

        $database = \Database::getInstance();
        $result   = $database
            ->prepare(sprintf('SELECT * FROM tl_calendar_events WHERE id IN (%s) ORDER BY startDate', $inSet))
            ->execute($ids);
        $nodes    = array();

        while ($result->next()) {
            $timestamps = $map[$result->id];

            foreach ($timestamps as $timestamp) {
                if (!$timestamp) {
                    $timestamp = $result->startDate;
                }

                $date = new \DateTime();
                $date->setTimestamp($timestamp);

                $nodes[] = new SelectriEventsEventNode($this, $result->row(), $date);
            }
        }

        return new \ArrayIterator($nodes);
    }

    /**
     * Returns an iterator over the children of the node identified by the given
     * primary key or an iterator over the root nodes, if no primary key value
     * is given.
     *
     * When recursivly traversing the structure through the node's
     * getChildrenIterator, all nodes for that all ancestors are unfolded (open)
     * are visited.
     *
     * Whether or not a node is considered unfolded is implementation specific,
     * but implementors are recommended to use the getUnfolded method of this
     * data's widget to determine a nodes unfolded state.
     *
     * @param string|null $start A primary key value in its string
     *                           representation or null
     *
     * @return \Iterator<SelectriNode> An iterator over nodes
     */
    public function getTreeIterator($start = null)
    {
        if (!$start) {
            $start = 0;
        }

        return array($this->fetchNodes(), $start);
    }

    /**
     * Returns an iterator over the roots nodes.
     *
     * When recursivly traversing the structure through the node's
     * getChildrenIterator, all nodes on levels, that are on the path down to
     * the given primary key, are visited.
     *
     * @param string $key A primary key value in its string
     *                    representation
     *
     * @return \Iterator<SelectriNode> An iterator over the root nodes
     */
    public function getPathIterator($key)
    {
        return new \EmptyIterator($key);
    }

    /**
     * Returns an iterator over nodes matching the given search query.
     *
     * The returned nodes should NOT be traversed recursivly through the node's
     * getChildrenIterator method.
     *
     * @param string $query The search query to match nodes against
     *
     * @return \Iterator<SelectriNode> An iterator over nodes matched by the given
     *        search query
     */
    public function getSearchIterator($query)
    {
        $keywords = preg_split('~\s+~', $query);
        $keywords = array_filter($keywords);

        if (empty($keywords)) {
            return new \EmptyIterator();
        }

        return $this->fetchNodes($keywords);
    }

    /**
     * @param array $searchKeywords
     *
     * @return \ArrayIterator
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function fetchNodes(array $searchKeywords = array())
    {
        if (empty($searchKeywords)) {
            return new \EmptyIterator;
        }

        $begin = new \DateTime();
        $begin->setTime(0, 0, 0);
        $begin->sub(new \DateInterval('P1M'));

        $end = clone $begin;
        $end->add(new \DateInterval('P1Y1M'));

        $where = array('(id > ?)');
        $args  = array(0);

        $eventId = explode('@', $searchKeywords[0]);
        if (count($searchKeywords)) {
            if (count($eventId) === 2) {
                $where = array('(id = ?)');
                $args  = array($eventId[0]);
                unset($searchKeywords[0]);
            }

            if (count($searchKeywords)) {
                $where[] = '(' . substr(str_repeat(' OR title LIKE ?', count($searchKeywords)), 4) . ')';

                foreach ($searchKeywords as $searchKeyword) {
                    $args[] = '%' . $searchKeyword . '%';
                }
            }
        }

        $database = \Database::getInstance();
        $result   = $database
            ->prepare(sprintf('SELECT * FROM tl_calendar_events WHERE %s ORDER BY startDate', implode('AND', $where)))
            ->execute($args);
        $nodes    = array();

        $generateFlat = count($searchKeywords) > 0;

        while ($result->next()) {
            $date = new \DateTime();
            $date->setTimestamp($result->startDate);

            if ($result->recurring) {
                $repeatEach = deserialize($result->repeatEach, true);

                switch ($repeatEach['unit']) {
                    case 'days':
                        $interval = new \DateInterval(sprintf('P%dD', $repeatEach['value']));
                        break;

                    case 'weeks':
                        $interval = new \DateInterval(sprintf('P%dW', $repeatEach['value']));
                        break;

                    case 'months':
                        $interval = new \DateInterval(sprintf('P%dM', $repeatEach['value']));
                        break;

                    case 'years':
                        $interval = new \DateInterval(sprintf('P%dY', $repeatEach['value']));
                        break;

                    default:
                        throw new \RuntimeException(sprintf('Invalid repeat unit "%s"', $repeatEach['unit']));
                }

                $recurrences = $result->recurrences > 0 ? (int) $result->recurrences : PHP_INT_MAX;
            } else {
                $recurrences = 1;
                $interval    = false;
            }

            do {
                if ($generateFlat) {
                    $nodes[] = new SelectriEventsEventNode($this, $result->row(), $date);
                } else {
                    $month = $date->format('Y-m');


                    if (isset($nodes[$month])) {
                        $monthNode = $nodes[$month];
                    } else {
                        $monthNode = $nodes[$month] = new SelectriEventsMonthNode($this, $date);
                    }

                    $monthNode->addEvent(new SelectriEventsEventNode($this, $result->row(), $date));
                }

                if ($interval) {
                    $date = clone $date;
                    $date->add($interval);
                }

                $recurrences--;
            } while ($recurrences && $date->getTimestamp() < $end->getTimestamp());
        }

        if ($generateFlat) {
            usort(
                $nodes,
                function (SelectriEventsEventNode $primary, SelectriEventsEventNode $secondary) {
                    return $primary->getDate()->getTimestamp() - $secondary->getDate()->getTimestamp();
                }
            );
        } else {
            ksort($nodes);
        }

        return new \ArrayIterator($nodes);
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNodes(array $keys, $selectableOnly = true)
    {
        return $this->fetchNodes($keys);
    }

    /**
     * @throws SelectriException If this data instance is not configured correctly
     * @return void
     */
    public function validate()
    {
        //no validation. this is always true
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isSearchable()
     */
    public function isSearchable()
    {
        return true;
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::isBrowsable()
     */
    public function isBrowsable()
    {
        return true;
    }

    /**
     * @see \Hofff\Contao\Selectri\Model\Data::search()
     *
     * @param string $query
     * @param int    $limit
     * @param int    $offset
     *
     * @return Iterator|\EmptyIterator|\Hofff\Contao\Selectri\Model\Iterator
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function search($query, $limit, $offset = 0)
    {
        return $this->getNodes(SearchUtil::parseKeywords($query));
    }
}
