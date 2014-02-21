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
 * Message elements
 */
$GLOBALS['TL_MCE']['includes'][] = 'event';

/**
 * Events
 */
$GLOBALS['TL_EVENT_SUBSCRIBERS'][] = 'Avisota\Contao\Message\Element\Event\DefaultRenderer';
