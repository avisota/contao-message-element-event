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

use SelectriWidget;

/**
 * Class SelectriEventsData
 */
class SelectriEventsData implements \SelectriData
{

    /**
     * @var SelectriWidget
     */
    protected $widget;

    /**
     * @return SelectriWidget The widget this data belongs to
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * @param SelectriWidget $widget
     *
     * @return static
     */
    public function setWidget($widget)
    {
        $this->widget = $widget;
        return $this;
    }

    /**
     * @throws \Exception If this data instance is not configured correctly
     * @return void
     */
    public function validate()
    {
        // no op, this data is always valid
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
    public function filter(array $selection)
    {
        return $selection;
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTreeIterator($start = null)
    {
        return array($this->fetchNodes(), 0);
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPathIterator($key)
    {
        return new \EmptyIterator();
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
        $begin = new \DateTime();
        $begin->setTime(0, 0, 0);
        $begin->sub(new \DateInterval('P1M'));

        $end = clone $begin;
        $end->add(new \DateInterval('P1Y1M'));

        $where = array('(startDate > ? OR recurring = 1)');
        $args  = array($begin->getTimestamp());

        if (count($searchKeywords)) {
            $where[] = '(' . substr(str_repeat(' OR title LIKE ?', count($searchKeywords)), 4) . ')';

            foreach ($searchKeywords as $searchKeyword) {
                $args[] = '%' . $searchKeyword . '%';
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
}
