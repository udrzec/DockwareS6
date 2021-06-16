<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\Store\Service;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\App\AppEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Store\Exception\ExtensionNotFoundException;
use Shopware\Core\Framework\Store\Services\AbstractExtensionDataProvider;
use Shopware\Core\Framework\Store\Services\StoreService;
use Shopware\Core\Framework\Store\Struct\ExtensionStruct;
use Shopware\Core\Framework\Test\Store\ExtensionBehaviour;
use Shopware\Core\Framework\Test\Store\StoreClientBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @group skip-paratest
 */
class ExtensionDataProviderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;
    use ExtensionBehaviour;

    /**
     * @var AbstractExtensionDataProvider
     */
    private $extensionDataProvider;

    /**
     * @var Context
     */
    private $context;

    public function setUp(): void
    {
        Feature::skipTestIfInActive('FEATURE_NEXT_12608', $this);
        $this->extensionDataProvider = $this->getContainer()->get(AbstractExtensionDataProvider::class);
        $this->context = $this->createAdminStoreContext();

        $this->installApp(__DIR__ . '/../_fixtures/TestApp');
    }

    protected function tearDown(): void
    {
        $this->removeApp(__DIR__ . '/../_fixtures/TestApp');
    }

    public function testItReturnsInstalledAppsAsExtensionCollection(): void
    {
        $this->getContainer()->get(SystemConfigService::class)->set(StoreService::CONFIG_KEY_STORE_LICENSE_DOMAIN, 'localhost');
        $this->getRequestHandler()->reset();
        $this->getRequestHandler()->append(new Response(200, [], '[]'));

        $installedExtensions = $this->extensionDataProvider->getInstalledExtensions($this->context, false);
        $installedExtension = $installedExtensions->get('TestApp');

        static::assertInstanceOf(ExtensionStruct::class, $installedExtension);
        static::assertNull($installedExtension->getId());
        static::assertEquals('Swag App Test', $installedExtension->getLabel());
    }

    public function testGetAppEntityFromTechnicalName(): void
    {
        static::assertInstanceOf(AppEntity::class, $this->extensionDataProvider->getAppEntityFromTechnicalName('TestApp', $this->context));
    }

    public function testGetAppEntityFromId(): void
    {
        $installedApp = $this->extensionDataProvider->getAppEntityFromTechnicalName('TestApp', $this->context);

        $app = $this->extensionDataProvider->getAppEntityFromId($installedApp->getId(), $this->context);
        static::assertEquals(
            $installedApp,
            $app
        );
    }

    public function testGetAppEntityFromTechnicalNameThrowsIfExtensionCantBeFound(): void
    {
        static::expectException(ExtensionNotFoundException::class);
        $this->extensionDataProvider->getAppEntityFromTechnicalName(Uuid::randomHex(), $this->context);
    }

    public function testGetAppEntityFromIdThrowsIfExtensionCantBeFound(): void
    {
        static::expectException(ExtensionNotFoundException::class);
        $this->extensionDataProvider->getAppEntityFromId(Uuid::randomHex(), $this->context);
    }

    public function testItLoadsRemoteExtensions(): void
    {
        $this->getContainer()->get(SystemConfigService::class)->set(StoreService::CONFIG_KEY_STORE_LICENSE_DOMAIN, 'localhost');
        $this->getRequestHandler()->reset();
        $this->getRequestHandler()->append(new Response(200, [], '{"data":[]}'));
        $this->getRequestHandler()->append(new Response(200, [], file_get_contents(__DIR__ . '/../_fixtures/responses/my-licenses.json')));

        $installedExtensions = $this->extensionDataProvider->getInstalledExtensions($this->context, true);
        static::assertEquals(7, $installedExtensions->count());
    }
}
