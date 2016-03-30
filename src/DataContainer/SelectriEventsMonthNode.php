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

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Image\GenerateHtmlEvent;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use Hofff\Contao\Selectri\Model\Node;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class SelectriEventsMonthNode
 */
class SelectriEventsMonthNode implements Node
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
        /** @var SelectriEventsEventNode $newsNode */
        $newsNode = $this->getEvents()[0];
        if (!$newsNode) {
            return $this->date->format('Y F');
        }

        $eventsData = $newsNode->getRow();
        $label = $this->date->format(\Config::get('dateFormat'));

        if ($eventsData['addTime']) {
            $label .= ' ' . date('H:i', $eventsData['startTime']);
        }

        if ($eventsData['endDate'] || $eventsData['addTime']) {
            $label .= ' -';
        }

        if ($eventsData['endDate'] && $eventsData['endDate'] > $eventsData['startDate']) {
            $seconds = $eventsData['endDate'] - $eventsData['startDate'];

            $endDate = clone $this->date;
            $endDate->add(new \DateInterval(sprintf('PT%dS', $seconds)));

            $label .= ' ' . $endDate->format(\Config::get('dateFormat'));
        }

        if ($eventsData['addTime']) {
            $label .= ' ' . date('H:i', $eventsData['endTime']);
        }

        $label .= ': ' . $eventsData['title'];
        $label .= '<span style="color: grey;"> [' . \CalendarModel::findByPk($eventsData['pid'])->title . ']</span>';
        $label .= $this->getInfo($eventsData);

        return $label;
    }

    /**
     * @param $eventsData
     *
     * @return string
     */
    protected function getInfo($eventsData)
    {
        $info = '<div style="margin-left: 16px; padding-top: 6px">';
        $info .= $this->getEditButton($eventsData);
        $info .= $this->getHeaderButton($eventsData);
        $info .= $this->getPublishedIcon($eventsData);
        $info .= '</div>';

        return $info;
    }

    /**
     * @param $eventsData
     *
     * @return string
     */
    protected function getEditButton($eventsData)
    {
        $urlBuilder = new UrlBuilder();
        $urlBuilder->setPath('contao/main.php')
            ->setQueryParameter('do', 'calendar')
            ->setQueryParameter('table', 'tl_content')
            ->setQueryParameter('id', $eventsData['id'])
            ->setQueryParameter('popup', 1)
            ->setQueryParameter('rt', \RequestToken::get());

        $button =
            '<a href="' . $urlBuilder->getUrl() . '" ' . $this->getOnClickOpenModalIFrame() . '>'
            . $this->getOperationImage('edit.gif')
            . '</a>';

        return $button;
    }

    /**
     * @param $eventsData
     *
     * @return string
     */
    protected function getHeaderButton($eventsData)
    {
        $urlBuilder = new UrlBuilder();
        $urlBuilder->setPath('contao/main.php')
            ->setQueryParameter('do', 'calendar')
            ->setQueryParameter('table', 'tl_calendar_events')
            ->setQueryParameter('act', 'edit')
            ->setQueryParameter('id', $eventsData['id'])
            ->setQueryParameter('popup', 1)
            ->setQueryParameter('rt', \RequestToken::get());

        $button =
            '<a href="' . $urlBuilder->getUrl() . '" ' . $this->getOnClickOpenModalIFrame() . '>'
            . $this->getOperationImage('header.gif')
            . '</a>';

        return $button;
    }

    /**
     * @param $eventsData
     *
     * @return string
     */
    protected function getPublishedIcon($eventsData)
    {
        $icon = 'visible.gif';
        if ($eventsData['published'] < 1) {
            $icon = 'invisible.gif';
        }

        return $this->getOperationImage($icon);
    }

    /**
     * @param $icon
     *
     * @return string
     */
    protected function getOperationImage($icon)
    {
        global $container;
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $container['event-dispatcher'];

        /** @var GenerateHtmlEvent $imageEvent */
        $imageEvent = $eventDispatcher->dispatch(
            ContaoEvents::IMAGE_GET_HTML,
            new GenerateHtmlEvent(
                $icon,
                '',
                'style="padding-left: 6px;"'
            )
        );

        return $imageEvent->getHtml();
    }

    /**
     * @return string
     */
    protected function getOnClickOpenModalIFrame()
    {
        return 'onclick="Backend.openModalIframe({\'width\':768,\'url\':this.href});return false"';
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
                function (SelectriEventsEventNode $primary, SelectriEventsEventNode $secondary) {
                    return $primary->getDate()->getTimestamp() - $secondary->getDate()->getTimestamp();
                }
            );
            $this->isSorted = true;
        }

        return new \ArrayIterator($this->events);
    }
}
