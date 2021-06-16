<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Lifecycle\Registration;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\Framework\App\Exception\AppRegistrationException;
use Shopware\Core\Framework\Store\Services\StoreClient;

/**
 * @internal only for use by the app-system
 */
class StoreHandshake implements AppHandshakeInterface
{
    private string $shopUrl;

    private string $appEndpoint;

    private string $appName;

    private string $shopId;

    private StoreClient $storeClient;

    private string $shopwareVersion;

    public function __construct(string $shopUrl, string $appEndpoint, string $appName, string $shopId, StoreClient $storeClient, string $shopwareVersion)
    {
        $this->shopUrl = $shopUrl;
        $this->appEndpoint = $appEndpoint;
        $this->appName = $appName;
        $this->shopId = $shopId;
        $this->storeClient = $storeClient;
        $this->shopwareVersion = $shopwareVersion;
    }

    public function assembleRequest(): RequestInterface
    {
        $date = new \DateTime();
        $uri = new Uri($this->appEndpoint);

        $uri = Uri::withQueryValues($uri, [
            'shop-id' => $this->shopId,
            'shop-url' => $this->shopUrl,
            'timestamp' => $date->getTimestamp(),
        ]);

        $signature = $this->signPayload($uri->getQuery());

        return new Request(
            'GET',
            $uri,
            [
                'shopware-app-signature' => $signature,
                'sw-version' => $this->shopwareVersion,
            ]
        );
    }

    public function fetchAppProof(): string
    {
        $proof = $this->shopId . $this->shopUrl . $this->appName;

        return $this->storeClient->signPayloadWithAppSecret($proof, $this->appName);
    }

    private function signPayload(string $payload): string
    {
        try {
            return $this->storeClient->signPayloadWithAppSecret($payload, $this->appName);
        } catch (\Exception $e) {
            throw new AppRegistrationException(
                sprintf('Could not sign payload with store secret for app: "%s"', $this->appName),
                0,
                $e
            );
        }
    }
}
