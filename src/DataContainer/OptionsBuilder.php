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

use Avisota\Contao\Entity\MessageContent;
use ContaoCommunityAlliance\Contao\Events\CreateOptions\CreateOptionsEventCallbackFactory;
use ContaoCommunityAlliance\DcGeneral\Contao\Callback\Callbacks;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OptionsBuilder implements EventSubscriberInterface
{

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            GetPropertyOptionsEvent::NAME => array(
                array('eventTemplateOptions'),
            ),
        );
    }

    public function eventTemplateOptions(GetPropertyOptionsEvent $event, $name, EventDispatcher $eventDispatcher)
    {
        $environment    = $event->getEnvironment();
        $dataDefinition = $environment->getDataDefinition();

        if ($dataDefinition->getName() !== 'orm_avisota_message_content'
            || $event->getPropertyName() !== 'eventTemplate'
        ) {
            return;
        }

        $options = Callbacks::callArgs(
            CreateOptionsEventCallbackFactory::createTemplateGroupCallback('event_')
        );

        /** @var MessageContent $messageContent */
        $messageContent = $event->getModel()->getEntity();
        $theme          = $messageContent->getMessage()->getCategory()->getLayout()->getTheme();
        if (empty($theme->getTemplateDirectory())) {
            $event->setOptions($options);

            return;
        }

        $themeTemplateDirectory = scandir(TL_ROOT . '/templates/' . $theme->getTemplateDirectory());
        foreach ($themeTemplateDirectory as $file) {
            if (substr($file, 0, strlen('event_')) !== 'event_') {
                continue;
            }

            $chunks = explode('.', $file);
            $chunks = array_reverse($chunks);
            unset($chunks[0]);
            $chunks = array_reverse($chunks);

            $template = implode('.', $chunks);
            if (!array_key_exists($template, $options)) {
                $options[$template] = $template . ' (' . $theme->getTemplateDirectory() . ')';
                continue;
            }

            if (strlen($options[$template]) === strlen($template)) {
                $options[$template] = $template . ' (' . $theme->getTemplateDirectory() . ')';
                continue;
            }

            $options[$template] = str_replace('(', '(' . $theme->getTemplateDirectory() . ', ', $options[$template]);
        }

        $event->setOptions($options);
    }
}
