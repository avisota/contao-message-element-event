<?php

/**
 * Avisota newsletter and mailing system
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
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
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->row['id'] . '@' . $this->date->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->row;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        $label = $this->date->format($GLOBALS['TL_CONFIG']['dateFormat']);

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

            $label .= ' ' . $endDate->format($GLOBALS['TL_CONFIG']['dateFormat']);
        }

        if ($this->row['addTime']) {
            $label .= ' ' . date('H:i', $this->row['endTime']);
        }

        $label .= ': ' . $this->row['title'];

        return $label;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalInputName($key)
    {
        $name = $this->data->getWidget()->getAdditionalInputBaseName();
        $name .= '[' . $this->getKey() . ']';
        $name .= '[' . $key . ']';
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        if (version_compare(VERSION, '3', '<')) {
            return 'system/modules/calendar/html/icon.gif';
        }

        return 'system/modules/calendar/assets/icon.gif';
    }

    /**
     * {@inheritdoc}
     */
    public function isSelectable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isOpen()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPath()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathIterator()
    {
        return new \ArrayIterator(array($this->getMonth()));
    }

    /**
     * {@inheritdoc}
     */
    public function hasItems()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemIterator()
    {
        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function hasSelectableDescendants()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenIterator()
    {
        return new \EmptyIterator();
    }
}
