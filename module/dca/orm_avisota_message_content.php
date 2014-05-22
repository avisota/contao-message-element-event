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


/**
 * Table orm_avisota_message_content
 * Entity Avisota\Contao:MessageContent
 */
$GLOBALS['TL_DCA']['orm_avisota_message_content']['metapalettes']['event'] = array
(
	'type'      => array('cell', 'type', 'headline'),
	'include'   => array('eventId', 'eventTemplate'),
	'expert'    => array(':hide', 'cssID', 'space'),
	'published' => array('invisible'),
);

$GLOBALS['TL_DCA']['orm_avisota_message_content']['fields']['eventId']       = array
(
	'label'     => &$GLOBALS['TL_LANG']['orm_avisota_message_content']['eventId'],
	'exclude'   => true,
	'inputType' => 'selectri',
	'eval'      => array(
		'min'  => 1,
		'data' => function () {
			/** @var SelectriContaoTableDataFactory $data */
			$data = SelectriContaoTableDataFactory::create();
			$data->setItemTable('tl_calendar_events');
			$data->getConfig()
				->setItemSearchColumns(array('title'));
			$data->getConfig()
				->setItemConditionExpr('tstamp > 0');
			$data->getConfig()
				->setItemOrderByExpr('startDate DESC');
			return $data;
		},
	),
	'field'     => array(
		'type'     => 'integer',
		'nullable' => true,
	),
);
$GLOBALS['TL_DCA']['orm_avisota_message_content']['fields']['eventTemplate'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['orm_avisota_message_content']['eventTemplate'],
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => \ContaoCommunityAlliance\Contao\Events\CreateOptions\CreateOptionsEventCallbackFactory::createTemplateGroupCallback(
		'event_'
	),
	'field'            => array(
		'type'     => 'string',
		'nullable' => true,
	),
);
