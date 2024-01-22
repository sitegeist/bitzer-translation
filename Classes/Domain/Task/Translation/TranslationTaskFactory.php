<?php

declare(strict_types=1);

namespace Sitegeist\Bitzer\Translation\Domain\Task\Translation;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\Flow\Annotations as Flow;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Service\UserService;
use Psr\Http\Message\UriInterface;
use Sitegeist\Bitzer\Domain\Object\ObjectRepository;
use Sitegeist\Bitzer\Domain\Task\ActionStatusType;
use Sitegeist\Bitzer\Domain\Task\NodeAddress;
use Sitegeist\Bitzer\Domain\Task\TaskClassName;
use Sitegeist\Bitzer\Domain\Task\TaskFactoryInterface;
use Sitegeist\Bitzer\Domain\Task\TaskIdentifier;
use Sitegeist\Bitzer\Domain\Agent\Agent;
use Sitegeist\Bitzer\Translation\Domain\Translation\TranslationAssignmentRepository;

/**
 * The translation task factory
 * Creates translation task objects with proper targets
 */
#[Flow\Scope('singleton')]
final class TranslationTaskFactory implements TaskFactoryInterface
{
    #[Flow\InjectConfiguration(path: 'languageDimension', package: 'Sitegeist.Bitzer.Translation')]
    protected string $languageDimension;

    public function __construct(
        private readonly ObjectRepository $objectRepository,
        private readonly UserService $userService,
        private readonly WorkspaceRepository $workspaceRepository,
        private readonly TranslationAssignmentRepository $translationAssignmentRepository,
    ) {
    }

    final public function createFromRawData(
        TaskIdentifier $identifier,
        TaskClassName $className,
        array $properties,
        \DateTimeImmutable $scheduledTime,
        ActionStatusType $actionStatus,
        Agent $agent,
        ?NodeAddress $object,
        ?UriInterface $target
    ): TranslationTask {
        if (!$object instanceof NodeAddress) {
            throw new \Exception('Cannot create a translation task without an object.', 1705923454);
        }

        $backendUser = $this->userService->getBackendUser();
        if (!$backendUser instanceof User) {
            throw new \Exception('Cannot create a translation task without a backend user agent.', 1705921382);
        }

        $workspace = $this->workspaceRepository->findOneByOwner($backendUser);
        if (!$workspace instanceof Workspace) {
            throw new \Exception('Cannot create a translation task without a backend user agent.', 1705921382);
        }

        $sourceDocumentNode = $this->findSourceDocumentNode($object, $workspace);
        $sourceAddress = NodeAddress::fromNode($sourceDocumentNode);
        $targetAddress = new NodeAddress(
            $workspace->getName(),
            $object->getDimensionSpacePoint(),
            $sourceAddress->getNodeAggregateIdentifier()
        );
        $resolvedObject = $this->objectRepository->findByAddress($targetAddress);

        $target = $resolvedObject
            ? $this->buildBackendUri($resolvedObject)
            : null;

        return new TranslationTask(
            $identifier,
            $properties,
            $scheduledTime,
            $actionStatus,
            $agent,
            $resolvedObject,
            $target
        );
    }

    private function findSourceDocumentNode(NodeAddress $object, Workspace $workspace): Node
    {
        $sourceLanguage = $this->translationAssignmentRepository->findSourceLanguage(
            $object->getDimensionSpacePoint()->getCoordinate(new ContentDimensionIdentifier($this->languageDimension))
        );
        $sourceCoordinates = $object->getDimensionSpacePoint()->getCoordinates();
        $sourceCoordinates[$this->languageDimension] = $sourceLanguage;
        $sourceAddress = new NodeAddress(
            $workspace->getName(),
            new DimensionSpacePoint($sourceCoordinates),
            $object->getNodeAggregateIdentifier()
        );

        $sourceObject = $this->objectRepository->findByAddress($sourceAddress);
        if (!$sourceObject instanceof Node) {
            throw new \Exception('Cannot create a translation task without an object.', 1705921697);
        }

        while ($sourceObject && !$sourceObject->getNodeType()->isOfType('Neos.Neos:Document')) {
            $sourceObject = $sourceObject->findParentNode();
        }

        return $sourceObject;
    }

    private function buildBackendUri(TraversableNodeInterface $object): Uri
    {
        /** @var NodeInterface $object */
        $httpRequest = ServerRequest::fromGlobals();
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        $uriBuilder->setCreateAbsoluteUri(true);

        return new Uri($uriBuilder->uriFor(
            'index',
            ['node' => $object],
            'Backend',
            'Neos.Neos.Ui'
        ));
    }
}
