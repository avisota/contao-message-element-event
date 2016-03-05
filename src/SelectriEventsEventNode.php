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
 * Class SelectriEventsEventNode
 */
class SelectriEventsEventNode implements \SelectriNode
{
    /**
     * @var SelectriEventsData
     */
    protected $data;

    /**
     * @var array
     */
    protected $row;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var SelectriEventsMonthNode
     */
    protected $month;

    /**
     * SelectriEventsEventNode constructor.
     *
     * @param SelectriEventsData $data
     * @param                    $row
     * @param \DateTime          $date
     */
    public function __construct(SelectriEventsData $data, $row, \DateTime $date)
    {
        $this->data = $data;
        $this->row  = $row;
        $this->date = $date;
    }

    /**
     * @return array
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return SelectriEventsMonthNode
     */
    public function getMonth()
    {
        if ($this->month) {
            return $this->month;
        }

        return new SelectriEventsMonthNode($this->data, $this->date);
    }

    /**
     * @param SelectriEventsMonthNode $month
     *
     * @return static
     */
    public function setMonth($month)
    {
        $this->month = $month;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->row['id'] . '@' . $this->date->getTimestamp();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->row;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        $label = $this->date->format(\Config::get('dateFormat'));

        if ($this->row['addTime']) {
            $label .= ' ' . date('H:i', $this->row['startTime']);
        }

        if ($this->row['endDate'] || $this->row['addTime']) {
            $label .= ' -';
        }

        if ($this->row['endDate'] && $this->row['endDate'] > $this->row['startDate']) {
            $seconds = $this->row['endDate'] - $this->row['startDate'];

            $endDate = clone $this->date;
            $endDate->add(new \DateInterval(sprintf('PT%dS', $seconds)));

            $label .= ' ' . $endDate->format(\Config::get('dateFormat'));
        }

        if ($this->row['addTime']) {
            $label .= ' ' . date('H:i', $this->row['endTime']);
        }

        $label .= ': ' . $this->row['title'];
        $label .= '<span style="color: grey;"> [' . \CalendarModel::findByPk($this->row['pid'])->title . ']</span>';

        return $label;
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
        return 'system/modules/calendar/assets/icon.gif';
    }

    /**
     * @return bool
     */
    public function isSelectable()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasPath()
    {
        return true;
    }

    /**
     * @return \ArrayIterator
     */
    public function getPathIterator()
    {
        return new \ArrayIterator(array($this->getMonth()));
    }

    /**
     * @return bool
     */
    public function hasItems()
    {
        return false;
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
        return false;
    }

    /**
     * @return \EmptyIterator
     */
    public function getChildrenIterator()
    {
        return new \EmptyIterator();
    }
}
