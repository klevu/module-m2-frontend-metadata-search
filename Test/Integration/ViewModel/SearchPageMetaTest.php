<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\FrontendMetadataSearch\Test\Integration\ViewModel;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\FrontendMetadata\ViewModel\PageMeta;
use Klevu\FrontendMetadataApi\Service\Provider\PageMetaProviderInterface;
use Klevu\FrontendMetadataApi\ViewModel\PageMetaInterface;
use Klevu\FrontendMetadataSearch\ViewModel\PageMeta\Search as SearchPageMetaVirtualType;
use Klevu\Registry\Api\ProductRegistryInterface;
use Klevu\TestFixtures\Catalog\ProductTrait;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\ProductFixturePool;

/**
 * @covers \Klevu\FrontendMetadata\ViewModel\PageMeta
 * @method PageMetaInterface instantiateTestObject(?array $arguments = null)
 * @method PageMetaInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class SearchPageMetaTest extends TestCase
{
    use ObjectInstantiationTrait;
    use ProductTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line
    /**
     * @var SerializerInterface|null
     */
    private ?SerializerInterface $serializer = null;
    /**
     * @var ScopeProviderInterface
     */
    private ScopeProviderInterface $scopeProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = SearchPageMetaVirtualType::class; // @phpstan-ignore-line
        $this->interfaceFqcn = PageMetaInterface::class;
        $this->implementationForVirtualType = PageMeta::class;

        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->productFixturePool = $this->objectManager->get(ProductFixturePool::class);
        $this->serializer = $this->objectManager->get(SerializerInterface::class);
        $this->scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $this->scopeProvider->unsetCurrentScope();

        $request = $this->objectManager->get(RequestInterface::class);
        $request->setModuleName('catalogsearch');
        $request->setControllerName('result');
        $request->setActionName('index');
        $request->setParams([
            'q' => 'jacket',
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->productFixturePool->rollback();
        $this->storeFixturesPool->rollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testGetMeta_ReturnsString_WhenEnabled(): void
    {
        $this->createProduct([
            'name' => 'Klevu Product Name',
            'price' => 1299.99123,
        ]);
        $viewModel = $this->instantiateTestObject();

        $productFixture = $this->productFixturePool->get('test_product');
        $productRegistry = $this->objectManager->get(ProductRegistryInterface::class);
        $currentProduct = $productFixture->getProduct();
        $productRegistry->setCurrentProduct($currentProduct);

        $viewModelPageMeta = $viewModel->getMeta();
        $pageMeta = $this->serializer->unserialize($viewModelPageMeta);

        $this->assertArrayHasKey('page', $pageMeta);
        $this->assertArrayHasKey('pageType', $pageMeta['page']);
        $this->assertSame(expected: 'srlp', actual: $pageMeta['page']['pageType']);

        $this->assertArrayNotHasKey(key: 'cart', array: $pageMeta['page']);

        $this->assertArrayHasKey(key: 'quick', array: $pageMeta['page']);
        $this->assertArrayHasKey(key: 'products', array: $pageMeta['page']['quick']);
        $this->assertIsArray(actual: $pageMeta['page']['quick']['products']);

        $this->assertArrayHasKey('srlp', $pageMeta['page']);
        $this->assertArrayHasKey('searchTerm', $pageMeta['page']['srlp']);
        $this->assertSame(
            expected: 'jacket',
            actual: $pageMeta['page']['srlp']['searchTerm'],
        );
        $this->assertArrayHasKey('searchUrl', $pageMeta['page']['srlp']);
        $this->assertStringContainsString(
            needle: 'catalogsearch/result?q=jacket',
            haystack: $pageMeta['page']['srlp']['searchUrl'],
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/klevu_frontend/metadata/enabled 0
     * @magentoConfigFixture default_store klevu_frontend/metadata/enabled 0
     * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/enable_reindex_schedule.php
     */
    public function testGetMeta_ReturnsFalse_WhenDisabled(): void
    {
        $mockProvider = $this->getMockBuilder(PageMetaProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockProvider->expects($this->never())
            ->method('get');

        $this->createProduct([
            'name' => 'Klevu Product Name',
            'price' => 99.99123,
        ]);
        $viewModel = $this->instantiateTestObject([
            'pageMetaProviders' => [
                'section' => $mockProvider,
            ],
        ]);

        $productFixture = $this->productFixturePool->get('test_product');
        $productRegistry = $this->objectManager->get(ProductRegistryInterface::class);
        $productRegistry->setCurrentProduct($productFixture->getProduct());

        $this->assertFalse(condition: $viewModel->isEnabled());
    }
}
