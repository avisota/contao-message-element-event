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

use Avisota\Contao\Core\Message\Renderer;
use Avisota\Contao\Entity\MessageContent;
use Avisota\Contao\Message\Core\Event\AvisotaMessageEvents;
use Avisota\Contao\Message\Core\Event\RenderMessageContentEvent;
use Avisota\Recipient\RecipientInterface;
use Contao\Doctrine\ORM\Entity;
use Contao\Doctrine\ORM\EntityAccessor;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Calendar\GetCalendarEventEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class DefaultRenderer
 */
class DefaultRenderer implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            AvisotaMessageEvents::RENDER_MESSAGE_CONTENT => 'renderContent',
        );
    }

    /**
     * Render a single message content element.
     *
     * @param MessageContent     $content
     * @param RecipientInterface $recipient
     *
     * @return string
     */
    public function renderContent(RenderMessageContentEvent $event)
    {
        $content = $event->getMessageContent();

        if ($content->getType() != 'event' || $event->getRenderedContent()) {
            return;
        }

        /** @var EntityAccessor $entityAccessor */
        $entityAccessor = $GLOBALS['container']['doctrine.orm.entityAccessor'];

        list($id, $timestamp) = explode('@', $content->getEventIdWithTimestamp());

        if ($timestamp) {
            $date = new \DateTime();
            $date->setTimestamp($timestamp);
        } else {
            $date = null;
        }

        $getCalendarEventEvent = new GetCalendarEventEvent(
            $id,
            $date,
            $content->getEventTemplate()
        );

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $GLOBALS['container']['event-dispatcher'];
        $eventDispatcher->dispatch(ContaoEvents::CALENDAR_GET_EVENT, $getCalendarEventEvent);

        $context          = $entityAccessor->getProperties($content);
        $context['event'] = $getCalendarEventEvent->getCalendarEventHtml();

        $template = new \TwigTemplate('avisota/message/renderer/default/mce_event', 'html');
        $buffer   = $template->parse($context);

        $event->setRenderedContent($buffer);
    }
}
