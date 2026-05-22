<?php

/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE: All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

class AIProviderResponse
{
    /**
     * @var string
     */
    private $providerId;

    /**
     * @var string
     */
    private $providerName;

    /**
     * @var string
     */
    private $model;

    /**
     * @var string
     */
    private $text;

    public function __construct(string $providerId, string $providerName, string $model, string $text)
    {
        $this->providerId = $providerId;
        $this->providerName = $providerName;
        $this->model = $model;
        $this->text = $text;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'providerId' => $this->providerId,
            'providerName' => $this->providerName,
            'model' => $this->model,
            'text' => $this->text,
        ];
    }
}
