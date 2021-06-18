<?php declare(strict_types=1);

namespace SwagExamplePlugin\Storefront\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class SwagExampleController extends StorefrontController
{
    /**
    * @Route("/example", name="frontend.example.example", methods={"GET"})
    */
    public function showExample(Request $request, Context $context): Response
    {
        return $this->renderStorefront('@SwagExamplePlugin/storefront/page/example.html.twig', [
            'example' => 'Hello world'
        ]);
    }
}