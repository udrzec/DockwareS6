<?php declare(strict_types=1);

namespace SwagExamplePlugin\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;



//$products = 0;

class ReadingData
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    
    public function readData(Context $context): void
    {
        $products = $this->productRepository->search(new Criteria(), $context);
    }
   
}
//echo $products;