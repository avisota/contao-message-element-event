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
 * Class SelectriEventsMonthNode
 */
class SelectriEventsMonthNode implements \SelectriNode
{

    /**
     * @var SelectriEventsData
     */
    protected $data;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var SelectriEventsEventNode[]
     */
    protected $events;

    /**
     * @var bool
     */
    protected $isSorted = false;

    /**
     * SelectriEventsMonthNode constructor.
     *
     * @param SelectriEventsData $data
     * @param \DateTime          $date
     */
    public function __construct(SelectriEventsData $data, \DateTime $date)
    {
        $this->data   = $data;
        $this->date   = $date;
        $this->events = array();
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return SelectriEventsEventNode[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param SelectriEventsEventNode[] $events
     *
     * @return static
     */
    public function setEvents(array $events)
    {
        $this->events = array();
        $this->addEvents($events);
        return $this;
    }

    /**
     * @param SelectriEventsEventNode[] $events
     *
     * @return static
     */
    public function addEvents(array $events)
    {
        foreach ($events as $event) {
            $this->addEvent($event);
        }
        return $this;
    }

    /**
     * @param SelectriEventsEventNode $event
     *
     * @return static
     * @internal param SelectriEventsEventNode $events
     *
     */
    public function addEvent(SelectriEventsEventNode $event)
    {
        $this->events[] = $event;
        $event->setMonth($this);
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->date->format('Y-m');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return array('date' => $this->date->getTimestamp());
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->date->format('Y F');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return '';
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function getAdditionalInputName($key)
    {
        $name = $this->data->getWidget()->getAdditionalInputBaseName();
        $name .= '[' . $this->getKey() . ']';
        $name .= '[' . $key . ']';
        return $name;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        if (version_compare(VERSION, '3', '<')) {
            return 'system/modules/calendar/html/icon.gif';
        }

        return 'system/modules/calendar/assets/icon.gif';
    }

    /**
     * @return bool
     */
    public function isSelectable()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasPath()
    {
        return false;
    }

    /**
     * @return \EmptyIterator
     */
    public function getPathIterator()
    {
        return new \EmptyIterator();
    }

    /**
     * @return bool
     */
    public function hasItems()
    {
        return (bool) count($this->events);
    }

    /**
     * @return \EmptyIterator
     */
    public function getItemIterator()
    {
        return new \EmptyIterator();
    }

    /**
     * @return bool
     */
    public function hasSelectableDescendants()
    {
        return true;
    }

    /**
     * @return \ArrayIterator
     */
    public function getChildrenIterator()
    {
        if (!$this->isSorted) {
            usort(
                $this->events,
                function (SelectriEventsEventNode $a, SelectriEventsEventNode $b) {
                    return $a->getDate()->getTimestamp() - $b->getDate()->getTimestamp();
                }
            );
            $this->isSorted = true;
        }

        return new \ArrayIterator($this->events);
    }
}
