<?php

declare(strict_types=1);

namespace Sitegeist\Bitzer\Translation;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Service\PublishingService;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package as BasePackage;
use Sitegeist\Bitzer\Translation\Domain\Task\Translation\TranslationTaskZookeeper;

/**
 * The Sitegeist.Bitzer.Translation package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(
            Node::class,
            'nodePropertyChanged',
            TranslationTaskZookeeper::class,
            'whenNodePropertiesWereSet'
        );

        $dispatcher->connect(
            Node::class,
            'nodeRemoved',
            TranslationTaskZookeeper::class,
            'whenNodeWasRemoved'
        );

        $dispatcher->connect(
            PublishingService::class,
            'nodeDiscarded',
            TranslationTaskZookeeper::class,
            'whenNodeWasRemoved'
        );

        $dispatcher->connect(
            PublishingService::class,
            'nodePublished',
            TranslationTaskZookeeper::class,
            'whenNodeWasPublished'
        );
    }
}
