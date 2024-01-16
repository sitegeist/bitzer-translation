<?php declare(strict_types=1);
namespace Sitegeist\Bitzer\Translation\Domain\Translation;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Bitzer\Domain\Agent\Agent;
use Sitegeist\Bitzer\Domain\Agent\AgentIdentifier;

/**
 * The approval assignment domain repository
 * @Flow\Scope("singleton")
 */
final class TranslationAssignmentRepository
{
    /**
     * @var array<string,array<string,array<string>>>
     */
    #[Flow\InjectConfiguration(path: 'agents', package: 'Sitegeist.Bitzer.Translation')]
    protected array $agents;

    /**
     * @return array<string,Agent>
     */
    public function findResponsibleAgentsForLanguage(string $referenceLanguage): array
    {
        $agentsByTargetLanguage = [];

        foreach ($this->agents[$referenceLanguage] as $targetLanguage => $agents) {
            $agentsByTargetLanguage[$targetLanguage] = array_map(
                fn(string $agent) => new Agent(
                    AgentIdentifier::fromString($agent),
                    mb_substr($agent, mb_strrpos($agent, ':') ?: 0)
                ),
                $agents
            );
        }

        return $agentsByTargetLanguage;
    }
}
