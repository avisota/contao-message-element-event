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
 * Class SelectriEventsDataFactory
 */
class SelectriEventsDataFactory extends \SelectriAbstractDataFactory
{
	/**
	 * {@inheritdoc}
	 */
	public function createData()
	{
		$data = new SelectriEventsData();
		$data->setWidget($this->getWidget());
		return $data;
	}
}
