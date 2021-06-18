<?php declare(strict_types=1);

namespace SwagExamplePlugin\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class WritingData
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $taxRepository;

    public function __construct(EntityRepositoryInterface $productRepository, EntityRepositoryInterface $taxRepository)
    {
        $this->productRepository = $productRepository;
        $this->taxRepository = $taxRepository;
    }
}