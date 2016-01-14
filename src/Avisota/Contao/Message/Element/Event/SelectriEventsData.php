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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function validate()
    {
        // no op, this data is always valid
    }

    /**
     * {@inheritdoc}
     */
    public function filter(array $selection)
    {
        return $selection;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getTreeIterator($start = null)
    {
        return array($this->fetchNodes(), 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getPathIterator($key)
    {
        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
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
                function (SelectriEventsEventNode $a, SelectriEventsEventNode $b) {
                    return $a->getDate()->getTimestamp() - $b->getDate()->getTimestamp();
                }
            );
        } else {
            ksort($nodes);
        }

        return new \ArrayIterator($nodes);
    }
}
