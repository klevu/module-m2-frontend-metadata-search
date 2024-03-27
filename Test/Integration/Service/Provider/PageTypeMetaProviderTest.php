<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\FrontendMetadataSearch\Test\Integration\Service\Provider;

use Klevu\FrontendMetadata\Service\Provider\PageTypeMetaProvider;
use Klevu\FrontendMetadataApi\Service\Provider\PageMetaProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers PageTypeMetaProvider
 * @method PageMetaProviderInterface instantiateTestObject(?array $arguments = null)
 * @method PageMetaProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class PageTypeMetaProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
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

        $this->implementationFqcn = PageTypeMetaProvider::class;
        $this->interfaceFqcn = PageMetaProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGet_ReturnsArray_IncludingSrlp_ForSearchPage(): void
    {
        $this->setRequest(
            module: 'catalogsearch',
            controller: 'result',
            action: 'index',
        );

        $provider = $this->instantiateTestObject();
        $result = $provider->get();

        $this->assertSame(expected: 'srlp', actual: $result);
    }

    /**
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param mixed[]|null $params
     *
     * @return void
     */
    private function setRequest(
        string $module,
        string $controller,
        string $action,
        ?array $params = [],
    ): void {
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setModuleName($module);
        $request->setControllerName($controller);
        $request->setActionName($action);
        foreach ($params as $param => $value) {
            $request->setParam($param, $value);
        }
    }
}
