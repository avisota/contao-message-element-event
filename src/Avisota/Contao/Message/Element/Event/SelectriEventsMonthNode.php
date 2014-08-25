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
	 * @param SelectriEventsEventNode $events
	 *
	 * @return static
	 */
	public function addEvent(SelectriEventsEventNode $event)
	{
		$this->events[] = $event;
		$event->setMonth($this);
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getKey()
	{
		return $this->date->format('Y-m');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getData()
	{
		return array('date' => $this->date->getTimestamp());
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->date->format('Y F');
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
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isOpen()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function hasPath()
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPathIterator()
	{
		return new \EmptyIterator();
	}

	/**
	 * {@inheritdoc}
	 */
	public function hasItems()
	{
		return (bool) count($this->events);
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
		return true;
	}

	/**
	 * {@inheritdoc}
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
