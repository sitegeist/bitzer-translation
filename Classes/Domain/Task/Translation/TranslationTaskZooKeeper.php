<?php declare(strict_types=1);
namespace Sitegeist\Bitzer\Transtion\Domain\Task\Transtion;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManager;
use Sitegeist\Bitzer\Translation\Domain\Translation\TranslationAssignmentRepository;


/**
 * The translation task handler event listener
 * @Flow\Scope("singleton")
 */
final class TranslationTaskZooKeeper
{
    #[Flow\InjectConfiguration(path: 'languageDimension', package: 'Sitegeist.Bitzer.Translation')]
    protected string $languageDimension;
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly TranslationAssignmentRepository $translationAssignmentRepository
    ) {
    }

    public function whenNodePropertiesWereSet(
        TraversableNodeInterface $node,
        string $propertyName,
        mixed $oldValue,
        mixed $newValue) {
        if(class_exists('Sitegeist\LostInTranslation\Domain\TranslatableProperty\TranslatablePropertyNamesFactory')) {

            /** @var  \Sitegeist\LostInTranslation\Domain\TranslatableProperty\TranslatablePropertyNamesFactory $translatablePropertyNamesFactory */
            $translatablePropertyNamesFactory = $this->objectManager->get('Sitegeist\LostInTranslation\Domain\TranslatableProperty\TranslatablePropertyNamesFactory');
            $translatableProperties = $translatablePropertyNamesFactory->createForNodeType($node->getNodeType());
            if ($translatableProperties->isTranslatable($propertyName)) {
                $referenceLanguage = $node->getDimensionSpacePoint()->getCoordinate(new ContentDimensionIdentifier($this->languageDimension));
                $agents = $this->translationAssignmentRepository->findResponsibleAgentsForLanguage($referenceLanguage);

            }
        }
    }
}
