<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\FrontendMetadataSearch\Service\Provider;

use Klevu\FrontendMetadataApi\Service\Provider\PageMetaProviderInterface;

class QuickSearchMetaProvider implements PageMetaProviderInterface
{
    /**
     * @return mixed[]
     */
    public function get(): array
    {
        return [
            'products' => [],
        ];
    }
}
