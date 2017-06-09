<?php

/**
 * Avisota newsletter and mailing system
 * Copyright © 2017 Sven Baumann
 *
 * PHP version 5
 *
 * @copyright  way.vision 2017
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @package    avisota/contao-message-element-article
 * @license    LGPL-3.0+
 * @filesource
 */

use Avisota\Contao\Message\Element\Event\DataContainer\MessageContent;
use Avisota\Contao\Message\Element\Event\DataContainer\OptionsBuilder;
use Avisota\Contao\Message\Element\Event\DefaultRenderer;

return array(
    new MessageContent(),
    new DefaultRenderer(),
    new OptionsBuilder(),
);
