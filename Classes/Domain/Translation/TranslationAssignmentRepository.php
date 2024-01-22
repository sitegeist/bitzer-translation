<?php

declare(strict_types=1);

namespace Sitegeist\Bitzer\Translation\Domain\Translation;

use Neos\Flow\Annotations as Flow;

/**
 * The translation assignment domain repository
 */
#[Flow\Scope('singleton')]
final class TranslationAssignmentRepository
{
    /**
     * @var array<string,array<string,mixed>>
     */
    #[Flow\InjectConfiguration(path: 'assignments')]
    protected array $assignments;

    /**
     * @return array<string,TranslationAssignment>
     */
    public function findAssignmentsForLanguage(string $referenceLanguage): array
    {
        $agentsByTargetLanguage = [];

        foreach ($this->assignments[$referenceLanguage] ?? [] as $targetLanguage => $assignment) {
            $agentsByTargetLanguage[$targetLanguage] = TranslationAssignment::fromArray($assignment);
        }

        return $agentsByTargetLanguage;
    }

    public function findSourceLanguage(string $referenceLanguage): ?string
    {
        foreach ($this->assignments as $sourceLanguage => $assignmentsPerTargetLanguage) {
            if (array_key_exists($referenceLanguage, $assignmentsPerTargetLanguage)) {
                return $sourceLanguage;
            }
        }

        return null;
    }
}
