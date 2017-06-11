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

namespace Avisota\Contao\Message\Element\Event\DataContainer;

use Contao\Message;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\BooleanCondition;
use ContaoCommunityAlliance\DcGeneral\Event\PreEditModelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for data container message content.
 */
class MessageContent implements EventSubscriberInterface
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
            PreEditModelEvent::NAME => array(
                array('handleIncludeLegend')
            )
        );
    }

    /**
     * Handle legend include. If the content type event isn't save invisible all properties in it.
     *
     * @param PreEditModelEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function handleIncludeLegend(PreEditModelEvent $event)
    {
        $environment    = $event->getEnvironment();
        $dataDefinition = $environment->getDataDefinition();
        $inputProvider  = $environment->getInputProvider();
        $translator     = $environment->getTranslator();

        if (array_key_exists('TL_INFO', $_SESSION)) {
            $infoMessageKey = array_search(
                $translator->translate('orm_avisota_message_content.event.saveBeforeSelect'),
                $_SESSION['TL_INFO'],
                true
            );

            if ($infoMessageKey && array_key_exists($infoMessageKey, $_SESSION['TL_INFO'])) {
                unset($_SESSION['TL_INFO'][$infoMessageKey]);
            }
        }

        if (!('orm_avisota_message_content' === $dataDefinition->getName())
            || !('event' === $inputProvider->getValue('type'))
        ) {
            return;
        }

        $entity = $event->getModel()->getEntity();

        if ($entity->getType() === $inputProvider->getValue('type')) {
            return;
        }

        $palettesDefinition = $dataDefinition->getPalettesDefinition();

        $palette = $palettesDefinition->getPaletteByName('event');

        $legend = $palette->getLegend('include');

        foreach ($legend->getProperties() as $property) {
            $property->setVisibleCondition(new BooleanCondition(false));
        }

        Message::addInfo($translator->translate('orm_avisota_message_content.event.saveBeforeSelect'));
    }
}
