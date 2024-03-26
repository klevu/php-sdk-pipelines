<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Traits;

use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Provider\UserAgent\PhpSDKUserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDKPipelines\Provider\UserAgent\SystemInformation\PhpPipelinesProvider;
use Klevu\PhpSDKPipelines\Provider\UserAgent\SystemInformation\PhpSDKPipelinesProvider;

trait InjectUserAgentProvidersTrait
{
    /**
     * @param UserAgentProviderInterface|null $userAgentProvider
     * @return void
     */
    private function injectUserAgentProviders(
        ?UserAgentProviderInterface $userAgentProvider,
    ): void {
        if (!($userAgentProvider instanceof ComposableUserAgentProviderInterface)) {
            return;
        }

        $phpSdkUserAgentProvider = $userAgentProvider->getUserAgentProviderByIdentifier(
            identifier: PhpSDKUserAgentProvider::PRODUCT_NAME,
        );
        if (!($phpSdkUserAgentProvider instanceof ComposableUserAgentProviderInterface)) {
            return;
        }

        $phpPipelinesUserAgentProvider = $phpSdkUserAgentProvider->getUserAgentProviderByIdentifier(
            identifier: PhpPipelinesProvider::PRODUCT_NAME,
        );
        if (!$phpPipelinesUserAgentProvider) {
            $phpSdkUserAgentProvider->addUserAgentProvider(
                userAgentProvider: new PhpPipelinesProvider(),
                identifier: PhpPipelinesProvider::PRODUCT_NAME,
            );
        }

        $phpSdkPipelinesUserAgentProvider = $phpSdkUserAgentProvider->getUserAgentProviderByIdentifier(
            identifier: PhpSDKPipelinesProvider::PRODUCT_NAME,
        );
        if (!$phpSdkPipelinesUserAgentProvider) {
            $phpSdkUserAgentProvider->addUserAgentProvider(
                userAgentProvider: new PhpSDKPipelinesProvider(),
                identifier: PhpSDKPipelinesProvider::PRODUCT_NAME,
            );
        }
    }
}
