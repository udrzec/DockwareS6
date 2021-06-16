<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Webhook\Hookable;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\Framework\Event\BusinessEventInterface;
use Shopware\Core\Framework\Webhook\BusinessEventEncoder;
use Shopware\Core\Framework\Webhook\Hookable;

class HookableEventFactory
{
    /**
     * @var BusinessEventEncoder
     */
    private $eventEncoder;

    /**
     * @var WriteResultMerger
     */
    private $writeResultMerger;

    public function __construct(BusinessEventEncoder $eventEncoder, WriteResultMerger $writeResultMerger)
    {
        $this->eventEncoder = $eventEncoder;
        $this->writeResultMerger = $writeResultMerger;
    }

    /**
     * @return Hookable[]
     */
    public function createHookablesFor($event): array
    {
        // BusinessEvent are the generic Events that get wrapped around the specific events
        // we don't want to dispatch those to the webhooks
        if ($event instanceof BusinessEvent) {
            return [];
        }

        if ($event instanceof Hookable) {
            return [$event];
        }

        if ($event instanceof BusinessEventInterface) {
            return [
                HookableBusinessEvent::fromBusinessEvent($event, $this->eventEncoder),
            ];
        }

        if ($event instanceof EntityWrittenContainerEvent) {
            return $this->wrapEntityWrittenEvent($event);
        }

        return [];
    }

    /**
     * @return Hookable[]
     */
    private function wrapEntityWrittenEvent(EntityWrittenContainerEvent $event): array
    {
        $hookables = [];
        foreach (HookableEventCollector::HOOKABLE_ENTITIES as $entity) {
            $writtenEvent = $event->getEventByEntityName($entity);

            if (!$writtenEvent) {
                continue;
            }

            $translationEvent = $event->getEventByEntityName($entity . '_translation');

            $mergedWrittenEvent = $this->writeResultMerger->mergeWriteResults($writtenEvent, $translationEvent);

            if (!$mergedWrittenEvent) {
                continue;
            }

            $hookables[] = HookableEntityWrittenEvent::fromWrittenEvent(
                $mergedWrittenEvent
            );
        }

        return $hookables;
    }
}
