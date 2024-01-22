<?php

declare(strict_types=1);

namespace Sitegeist\Bitzer\Translation\Domain\Task\Translation;

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\UriInterface;
use Sitegeist\Bitzer\Domain\Task\ActionStatusType;
use Sitegeist\Bitzer\Domain\Task\TaskIdentifier;
use Sitegeist\Bitzer\Domain\Task\TaskInterface;
use Sitegeist\Bitzer\Domain\Agent\Agent;

/**
 * The translation task domain entity
 */
#[Flow\Proxy(false)]
final class TranslationTask implements TaskInterface
{
    /**
     * @param array<string,mixed> $properties
     */
    public function __construct(
        private readonly TaskIdentifier $identifier,
        private readonly array $properties,
        private readonly \DateTimeImmutable $scheduledTime,
        private readonly ActionStatusType $actionStatus,
        private readonly Agent $agent,
        private readonly ?TraversableNodeInterface $object,
        private readonly ?UriInterface $target
    ) {
    }

    public static function getShortType(): string
    {
        return 'translation';
    }

    public function getIdentifier(): TaskIdentifier
    {
        return $this->identifier;
    }

    /**
     * The image describing the task. Must be a FontAwesome icon identifier available to the Neos UI.
     */
    public function getImage(): string
    {
        return 'check-double';
    }

    /**
     * A description of the task.
     */
    public function getDescription(): string
    {
        return $this->properties['description'] ?? '';
    }

    /**
     * The time the object is scheduled to.
     */
    public function getScheduledTime(): \DateTimeImmutable
    {
        return $this->scheduledTime;
    }

    /**
     * Indicates the current disposition of the Action.
     */
    public function getActionStatus(): ActionStatusType
    {
        return $this->actionStatus;
    }

    /**
     * The direct performer or driver of the action (animate or inanimate). e.g. John wrote a book.
     * In our case, as tasks are assigned to user groups, this is a Flow policy role identifier.
     */
    public function getAgent(): Agent
    {
        return $this->agent;
    }

    /**
     * The object upon which the action is carried out, whose state is kept intact or changed.
     * Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't).
     *
     * For now, we expect that only nodes are affected by tasks, if at all.
     */
    public function getObject(): ?TraversableNodeInterface
    {
        return $this->object;
    }

    /**
     * Indicates a target EntryPoint for an Action.
     *
     * In our case this is the URI for the next action to be done within this task.
     */
    public function getTarget(): ?UriInterface
    {
        return $this->target;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
