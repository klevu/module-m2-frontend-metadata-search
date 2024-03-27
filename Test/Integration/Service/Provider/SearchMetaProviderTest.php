<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\FrontendMetadataSearch\Test\Integration\Service\Provider;

use Klevu\FrontendMetadataApi\Service\Provider\PageMetaProviderInterface;
use Klevu\FrontendMetadataSearch\Service\Provider\SearchMetaProvider;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\FrontendMetadataSearch\Service\Provider\SearchMetaProvider
 * @method PageMetaProviderInterface instantiateTestObject(?array $arguments = null)
 * @method PageMetaProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class SearchMetaProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = SearchMetaProvider::class;
        $this->interfaceFqcn = PageMetaProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture default/klevu_frontend/metadata/enabled 1
     * @magentoConfigFixture default_store klevu_frontend/metadata/enabled 1
     * @magentoConfigFixture default_store klevu_frontend/quick_search/search_query_parameter query
     */
    public function testGet_ReturnsData_WhenEnabled(): void
    {
        $term = 'white shirt';
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['query' => $term]);

        $provider = $this->instantiateTestObject();
        $actualResult = $provider->get();
        $expectedArray = [
            'searchTerm' => $term,
            'searchUrl' => '/catalogsearch/result?query=' . $term,
        ];
        foreach ($expectedArray as $expectedArrayKey => $expectedArrayValue) {
            $this->assertArrayHasKey($expectedArrayKey, $actualResult);
            $this->assertSame(
                $expectedArrayValue,
                $actualResult[$expectedArrayKey],
            );
        }
    }

    /**
     * @magentoConfigFixture default/klevu_frontend/metadata/enabled 1
     * @magentoConfigFixture default_store klevu_frontend/metadata/enabled 1
     * @magentoConfigFixture default_store klevu_frontend/quick_search/search_query_parameter query
     * @magentoConfigFixture default/catalog/search/max_query_length 5
     * @magentoConfigFixture default_store catalog/search/max_query_length 5
     */
    public function testGet_ReturnsData_WhenEnabled_MaxCharsExceeded(): void
    {
        $term = 'white shirt';

        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['query' => $term]);

        $provider = $this->instantiateTestObject();
        $actualResult = $provider->get();
        $expectedArray = [
            'searchTerm' => 'white',
            'searchUrl' => '/catalogsearch/result?query=white',
        ];
        foreach ($expectedArray as $expectedArrayKey => $expectedArrayValue) {
            $this->assertArrayHasKey($expectedArrayKey, $actualResult);
            $this->assertSame(
                $expectedArrayValue,
                $actualResult[$expectedArrayKey],
            );
        }
    }
}
