/*!
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret
 * or copyright law. Redistribution of this information or reproduction of this material is
 * strictly forbidden unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from
 * InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

export interface ProviderConfiguration {
  apiKey: string;
  endpointUrl: string;
}

export interface Provider {
  id: string;
  name: string;
  description: string;
  supportsCustomEndpoint: boolean;
  configuration: {
    hasApiKey: boolean;
    endpointUrl: string;
  };
}

export interface CapabilityLevel {
  label: string;
  description: string;
}

export interface Settings {
  defaultProviderId: string;
  defaultCapabilityLevel: string;
  canEditProviderConfiguration: boolean;
  canEditCapabilityLevel: boolean;
  capabilityLevels: Record<string, CapabilityLevel>;
  providers: Provider[];
}

export interface CapabilityLevelOption {
  id: string;
  label: string;
  description: string;
}
