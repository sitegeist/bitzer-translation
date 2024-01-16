<?php declare(strict_types=1);
namespace Sitegeist\Bitzer\Translation;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Service\PublishingService;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package as BasePackage;
use Sitegeist\Bitzer\Approval\Domain\Task\Approval\ApprovalTaskZookeeper;
use Sitegeist\Bitzer\Transtion\Domain\Task\Transtion\TranslationTaskZooKeeper;

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
            TranslationTaskZooKeeper::class,
            'whenNodePropertiesWereSet'
        );
    }
}
