<?php declare(strict_types=1);

namespace Shopware\Core\Content\Category\SalesChannel;

use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Category\Event\CategoryRouteCacheKeyEvent;
use Shopware\Core\Content\Category\Event\CategoryRouteCacheTagsEvent;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductBoxStruct;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\Adapter\Cache\CacheCompressor;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @RouteScope(scopes={"store-api"})
 */
class CachedCategoryRoute extends AbstractCategoryRoute
{
    private AbstractCategoryRoute $decorated;

    private TagAwareAdapterInterface $cache;

    private EntityCacheKeyGenerator $generator;

    private AbstractCacheTracer $tracer;

    private array $states;

    private EventDispatcherInterface $dispatcher;

    private LoggerInterface $logger;

    public function __construct(
        AbstractCategoryRoute $decorated,
        TagAwareAdapterInterface $cache,
        EntityCacheKeyGenerator $generator,
        AbstractCacheTracer $tracer,
        EventDispatcherInterface $dispatcher,
        array $states,
        LoggerInterface $logger
    ) {
        $this->decorated = $decorated;
        $this->cache = $cache;
        $this->generator = $generator;
        $this->tracer = $tracer;
        $this->states = $states;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public static function buildName(string $id): string
    {
        return 'category-route-' . $id;
    }

    public function getDecorated(): AbstractCategoryRoute
    {
        return $this->decorated;
    }

    /**
     * @Since("6.2.0.0")
     * @OA\Post(
     *      path="/category/{categoryId}",
     *      summary="Loads a category with the resolved cms page",
     *      operationId="readCategory",
     *      tags={"Store API", "Content"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="categoryId", description="Id of the category", type="string", format="uuid")
     *          )
     *      ),
     *      @OA\Parameter(name="Api-Basic-Parameters"),
     *      @OA\Parameter(name="categoryId", description="Category ID", @OA\Schema(type="string"), in="path", required=true),
     *      @OA\Response(
     *          response="200",
     *          description="The loaded category with cms page",
     *          @OA\JsonContent(ref="#/components/schemas/category_flat")
     *     ),
     *     @OA\Response(
     *          response="404",
     *          ref="#/components/responses/404"
     *     ),
     * )
     *
     * @Route("/store-api/category/{navigationId}", name="store-api.category.detail", methods={"GET","POST"})
     */
    public function load(string $navigationId, Request $request, SalesChannelContext $context): CategoryRouteResponse
    {
        if ($context->hasState(...$this->states)) {
            $this->logger->info('cache-miss: ' . self::buildName($navigationId));

            return $this->getDecorated()->load($navigationId, $request, $context);
        }

        $item = $this->cache->getItem(
            $this->generateKey($navigationId, $request, $context)
        );

        try {
            if ($item->isHit() && $item->get()) {
                $this->logger->info('cache-hit: ' . self::buildName($navigationId));

                return CacheCompressor::uncompress($item);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        $this->logger->info('cache-miss: ' . self::buildName($navigationId));

        $name = self::buildName($navigationId);
        $response = $this->tracer->trace($name, function () use ($navigationId, $request, $context) {
            return $this->getDecorated()->load($navigationId, $request, $context);
        });

        $item = CacheCompressor::compress($item, $response);

        $item->tag($this->generateTags($navigationId, $response, $request, $context));

        $this->cache->save($item);

        return $response;
    }

    private function generateKey(string $navigationId, Request $request, SalesChannelContext $context): string
    {
        $parts = array_merge(
            $request->query->all(),
            $request->request->all(),
            [
                self::buildName($navigationId),
                $this->generator->getSalesChannelContextHash($context),
            ]
        );

        $event = new CategoryRouteCacheKeyEvent($navigationId, $parts, $request, $context, null);
        $this->dispatcher->dispatch($event);

        return md5(JsonFieldSerializer::encodeJson($event->getParts()));
    }

    private function generateTags(string $navigationId, CategoryRouteResponse $response, Request $request, SalesChannelContext $context): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName($navigationId)),
            $this->extractProductIds($response),
            [self::buildName($navigationId)]
        );

        $event = new CategoryRouteCacheTagsEvent($navigationId, $tags, $request, $response, $context, null);
        $this->dispatcher->dispatch($event);

        return array_unique(array_filter($event->getTags()));
    }

    private function extractProductIds(CategoryRouteResponse $response): array
    {
        $page = $response->getCategory()->getCmsPage();

        if ($page === null) {
            return [];
        }

        $ids = [];

        $slots = $page->getElementsOfType('product-slider');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $slider = $slot->getData();

            if (!$slider instanceof ProductSliderStruct) {
                continue;
            }

            if ($slider->getProducts() === null) {
                continue;
            }
            foreach ($slider->getProducts() as $product) {
                $ids[] = $product->getId();
                $ids[] = $product->getParentId();
            }
        }

        $slots = $page->getElementsOfType('product-box');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $box = $slot->getData();

            if (!$box instanceof ProductBoxStruct) {
                continue;
            }
            if ($box->getProduct() === null) {
                continue;
            }

            $ids[] = $box->getProduct()->getId();
            $ids[] = $box->getProduct()->getParentId();
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return array_merge(
            array_map([EntityCacheKeyGenerator::class, 'buildProductTag'], $ids),
            [EntityCacheKeyGenerator::buildCmsTag($page->getId())]
        );
    }
}
