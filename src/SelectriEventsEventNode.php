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

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Image\GenerateHtmlEvent;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
        $label .= $this->getInfo();

        return $label;
    }

    /**
     * @return string
     */
    protected function getInfo()
    {
        $info = '<div style="margin-left: 16px; padding-top: 6px">';
        $info .= $this->getEditButton();
        $info .= $this->getHeaderButton();
        $info .= $this->getPublishedIcon();
        $info .= '</div>';

        return $info;
    }

    /**
     * @return string
     */
    protected function getEditButton()
    {
        $urlBuilder = new UrlBuilder();
        $urlBuilder->setPath('contao/main.php')
            ->setQueryParameter('do', 'calendar')
            ->setQueryParameter('table', 'tl_content')
            ->setQueryParameter('id', $this->row['id'])
            ->setQueryParameter('popup', 1)
            ->setQueryParameter('rt', \RequestToken::get());

        $button = '<a href="' . $urlBuilder->getUrl() . '" ' . $this->getOnClickOpenModalIFrame() . '>' . $this->getOperationImage('edit.gif') . '</a>';

        return $button;
    }

    /**
     * @return string
     */
    protected function getHeaderButton()
    {
        $urlBuilder = new UrlBuilder();
        $urlBuilder->setPath('contao/main.php')
            ->setQueryParameter('do', 'calendar')
            ->setQueryParameter('table', 'tl_calendar_events')
            ->setQueryParameter('act', 'edit')
            ->setQueryParameter('id', $this->row['id'])
            ->setQueryParameter('popup', 1)
            ->setQueryParameter('rt', \RequestToken::get());

        $button = '<a href="' . $urlBuilder->getUrl() . '" ' . $this->getOnClickOpenModalIFrame() . '>' . $this->getOperationImage('header.gif') . '</a>';

        return $button;
    }

    /**
     * @return string
     */
    protected function getPublishedIcon()
    {
        $icon = 'visible.gif';
        if ($this->row['published'] < 1) {
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
