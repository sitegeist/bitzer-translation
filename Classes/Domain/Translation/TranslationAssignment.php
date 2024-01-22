<?php

declare(strict_types=1);

namespace Sitegeist\Bitzer\Translation\Domain\Translation;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Bitzer\Domain\Agent\Agent;
use Sitegeist\Bitzer\Domain\Agent\AgentIdentifier;
use Sitegeist\Bitzer\Domain\Agent\AgentType;

#[Flow\Proxy(false)]
final class TranslationAssignment
{
    /**
     * @param array<Agent> $agents
     */
    public function __construct(
        public readonly array $agents,
        public readonly \DateInterval $interval,
    ) {
    }

    public static function fromArray(array $configuration): self
    {
        return new self(
            array_map(
                fn (string $roleId): Agent => new Agent(
                    new AgentIdentifier(
                        AgentType::role(),
                        $roleId
                    ),
                    \mb_substr($roleId, mb_strrpos($roleId, ':') ?: 0),
                ),
                $configuration['agents']
            ),
            new \DateInterval($configuration['interval'])
        );
    }
}
