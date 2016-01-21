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

/**
 * Table orm_avisota_message_content
 * Entity Avisota\Contao:MessageContent
 */
$GLOBALS['TL_DCA']['orm_avisota_message_content']['metapalettes']['event'] = array
(
    'type'      => array('cell', 'type', 'headline'),
    'include'   => array('eventIdWithTimestamp', 'eventTemplate'),
    'expert'    => array(':hide', 'cssID', 'space'),
    'published' => array('invisible'),
);

$GLOBALS['TL_DCA']['orm_avisota_message_content']['fields']['eventIdWithTimestamp'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['orm_avisota_message_content']['eventIdWithTimestamp'],
    'exclude'   => true,
    'inputType' => 'selectri',
    'eval'      => array(
        'min'  => 1,
        'data' => function () {
            return \Avisota\Contao\Message\Element\Event\SelectriEventsDataFactory::create();
        },
    ),
    'field'     => array(
        'type'     => 'string',
        'nullable' => true,
    ),
);
$GLOBALS['TL_DCA']['orm_avisota_message_content']['fields']['eventTemplate']        = array
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