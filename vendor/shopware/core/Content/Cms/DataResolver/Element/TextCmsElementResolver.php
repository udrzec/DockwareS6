<?php declare(strict_types=1);

namespace Shopware\Core\Content\Cms\DataResolver\Element;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\TextStruct;

class TextCmsElementResolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return 'text';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $text = new TextStruct();
        $slot->setData($text);

        $config = $slot->getFieldConfig()->get('content');
        if (!$config) {
            return;
        }

        if ($config->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $content = $this->resolveEntityValueToString($resolverContext->getEntity(), $config->getValue(), $resolverContext);

            $text->setContent($content);
        }

        if ($config->isStatic()) {
            if ($resolverContext instanceof EntityResolverContext) {
                $content = $this->resolveEntityValues($resolverContext, (string) $config->getValue());

                $text->setContent((string) $content);
            } else {
                $text->setContent((string) $config->getValue());
            }
        }
    }
}
