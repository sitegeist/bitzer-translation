<?php

declare(strict_types=1);

namespace Sitegeist\Bitzer\Translation\Domain\Task\Translation;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Sitegeist\Bitzer\Application\Bitzer;
use Sitegeist\Bitzer\Domain\Task\Command\CancelTask;
use Sitegeist\Bitzer\Domain\Task\Command\CompleteTask;
use Sitegeist\Bitzer\Domain\Task\Command\ScheduleTask;
use Sitegeist\Bitzer\Domain\Task\NodeAddress;
use Sitegeist\Bitzer\Domain\Task\Schedule;
use Sitegeist\Bitzer\Domain\Task\TaskClassName;
use Sitegeist\Bitzer\Domain\Task\TaskIdentifier;
use Sitegeist\Bitzer\Translation\Domain\Translation\TranslationAssignmentRepository;

/**
 * The translation task zookeeper event listener
 */
#[Flow\Scope('singleton')]
final class TranslationTaskZookeeper
{
    #[Flow\InjectConfiguration(path: 'languageDimension', package: 'Sitegeist.Bitzer.Translation')]
    protected string $languageDimension;

    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly TranslationAssignmentRepository $translationAssignmentRepository,
        private readonly ContentDimensionPresetSourceInterface $contentDimensionSource,
        private readonly Schedule $schedule,
        private readonly Bitzer $bitzer,
        private readonly ContentContextFactory $contentContextFactory,
    ) {
    }

    public function whenNodePropertiesWereSet(
        Node $node,
        string $propertyName,
        mixed $oldValue,
        mixed $newValue
    ): void {
        if (class_exists('Sitegeist\LostInTranslation\Domain\TranslatableProperty\TranslatablePropertyNamesFactory')) {
            /** @var \Sitegeist\LostInTranslation\Domain\TranslatableProperty\TranslatablePropertyNamesFactory $translatablePropertyNamesFactory */
            $translatablePropertyNamesFactory = $this->objectManager->get('Sitegeist\LostInTranslation\Domain\TranslatableProperty\TranslatablePropertyNamesFactory');
            $translatableProperties = $translatablePropertyNamesFactory->createForNodeType($node->getNodeType());
            if ($translatableProperties->isTranslatable($propertyName)) {
                $languageDimensionId = new ContentDimensionIdentifier($this->languageDimension);
                $subject = NodeAddress::fromNodeData($node->getNodeData());
                $referenceLanguage = $subject->getDimensionSpacePoint()->getCoordinate($languageDimensionId);
                $assignmentsByTargetLanguage = $this->translationAssignmentRepository->findAssignmentsForLanguage($referenceLanguage);
                $presets = $this->contentDimensionSource->getAllPresets()[(string)$languageDimensionId]['presets'];
                $subject = NodeAddress::fromNodeData($node->getNodeData());
                $taskClassName = TaskClassName::createFromString(TranslationTask::class);
                foreach ($presets as $languageValue => $preset) {
                    if (array_key_exists($languageValue, $assignmentsByTargetLanguage)) {
                        $object = new NodeAddress(
                            'live',
                            $subject->getDimensionSpacePoint()->vary($languageDimensionId, $languageValue),
                            $subject->getNodeAggregateIdentifier()
                        );
                        $assignment = $assignmentsByTargetLanguage[$languageValue];

                        $activeAgents = [];
                        $tasks = $this->schedule->findActiveOrPotentialTasksForObject($object, $taskClassName);
                        foreach ($tasks as $task) {
                            $activeAgents[$task->getAgent()->getIdentifier()->toString()] = true;
                        }
                        foreach ($assignment->agents as $agent) {
                            if (array_key_exists($agent->getIdentifier()->toString(), $activeAgents)) {
                                continue;
                            }
                            $this->bitzer->handleScheduleTask(new ScheduleTask(
                                TaskIdentifier::create(),
                                $taskClassName,
                                (new \DateTimeImmutable())->add($assignment->interval),
                                $agent,
                                $object,
                                null,
                                ['description' => 'auto generated translation task']
                            ));
                        }
                    }
                }
            }
        }
    }

    public function whenNodeWasRemoved(Node $node): void
    {
        $contextProperties = $node->getContext()->getProperties();
        $contextProperties['workspaceName'] = 'live';
        $liveContentContext = $this->contentContextFactory->create($contextProperties);
        $nodeInLiveWorkspace = $liveContentContext->getNodeByIdentifier($node->getIdentifier());
        if ($nodeInLiveWorkspace instanceof Node) {
            // the node still exists in live and thus still might have to be translated
            return;
        }
        $subject = NodeAddress::fromNodeData($node->getNodeData());
        $languageDimensionId = new ContentDimensionIdentifier($this->languageDimension);

        foreach (
            $this->translationAssignmentRepository->findAssignmentsForLanguage(
                $subject->getDimensionSpacePoint()->getCoordinate($languageDimensionId)
            ) as $targetLanguage => $assignments
        ) {
            $object = new NodeAddress(
                'live',
                $subject->getDimensionSpacePoint()->vary($languageDimensionId, $targetLanguage),
                $subject->getNodeAggregateIdentifier()
            );
            $activeTranslationTaskIds = $this->schedule->findActiveOrPotentialTaskIdsForObject(
                $object,
                TaskClassName::createFromString(TranslationTask::class)
            );
            foreach ($activeTranslationTaskIds as $activeTranslationTaskId) {
                $this->bitzer->handleCancelTask(new CancelTask(
                    $activeTranslationTaskId
                ));
            }
        }
    }

    public function whenNodeWasPublished(Node $node, Workspace $targetWorkspace): void
    {
        if ($targetWorkspace->getName() === 'live') {
            $object = NodeAddress::fromNodeData($node->getNodeData());

            $activeTranslationTaskIds = $this->schedule->findActiveOrPotentialTaskIdsForObject(
                $object,
                TaskClassName::createFromString(TranslationTask::class)
            );
            foreach ($activeTranslationTaskIds as $activeTranslationTaskId) {
                $this->bitzer->handleCompleteTask(new CompleteTask(
                    $activeTranslationTaskId,
                ));
            }
        }
    }
}
