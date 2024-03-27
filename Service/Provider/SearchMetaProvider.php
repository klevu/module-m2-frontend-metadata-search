<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\FrontendMetadataSearch\Service\Provider;

use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\FrontendMetadataApi\Service\Provider\PageMetaProviderInterface;
use Klevu\FrontendSearch\Service\Provider\QueryTextProviderInterface;
use Magento\Search\Model\QueryFactory;

class SearchMetaProvider implements PageMetaProviderInterface
{
    /**
     * @var SettingsProviderInterface
     */
    private SettingsProviderInterface $landingUrlProvider;
    /**
     * @var QueryTextProviderInterface
     */
    private QueryTextProviderInterface $queryTextProvider;
    /**
     * @var SettingsProviderInterface
     */
    private SettingsProviderInterface $queryParamProvider;

    /**
     * @param SettingsProviderInterface $landingUrlProvider
     * @param QueryTextProviderInterface $queryTextProvider
     * @param SettingsProviderInterface $queryParamProvider
     */
    public function __construct(
        SettingsProviderInterface $landingUrlProvider,
        QueryTextProviderInterface $queryTextProvider,
        SettingsProviderInterface $queryParamProvider,
    ) {
        $this->landingUrlProvider = $landingUrlProvider;
        $this->queryTextProvider = $queryTextProvider;
        $this->queryParamProvider = $queryParamProvider;
    }

    /**
     * @return mixed[]
     */
    public function get(): array
    {
        $queryText = $this->queryTextProvider->get();

        return [
            'searchTerm' => $queryText,
            'searchUrl' => $this->getSearchUrl($queryText),
        ];
    }

    /**
     * @param string $queryText
     *
     * @return string
     */
    private function getSearchUrl(string $queryText): string
    {
        try {
            $queryParam = $this->queryParamProvider->get();
        } catch (OutputDisabledException) {
            $queryParam = QueryFactory::QUERY_VAR_NAME;
        }

        return $this->getLandingUrl()
            . '?' . $queryParam
            . '=' . $queryText;
    }

    /**
     * @return string
     */
    private function getLandingUrl(): string
    {
        try {
            $landingUrl = $this->landingUrlProvider->get();
        } catch (OutputDisabledException) {
            // this is never thrown by the calling code, but is in the interface
            $landingUrl = '';
        }

        return $landingUrl;
    }
}
