<!--
  Copyright (C) InnoCraft Ltd - All rights reserved.

  NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
  The intellectual and technical concepts contained herein are protected by trade secret
  or copyright law. Redistribution of this information or reproduction of this material is
  strictly forbidden unless prior written permission is obtained from InnoCraft Ltd.

  You shall use this code only in accordance with the license agreement obtained from
  InnoCraft Ltd.

  @link https://www.innocraft.com/
  @license For license details see https://www.innocraft.com/license
-->

<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
} from 'vue';
import {
  ActivityIndicator,
  AjaxHelper,
  Alert,
  AutoClearPassword as vAutoClearPassword,
  ContentBlock,
  NotificationsStore,
  translate,
} from 'CoreHome';
import {
  Field,
  Form as vForm,
  SaveButton,
} from 'CorePluginsAdmin';

interface ProviderConfiguration {
  apiKey: string;
  endpointUrl: string;
}

interface Provider {
  id: string;
  name: string;
  description: string;
  supportsCustomEndpoint: boolean;
  configuration: {
    hasApiKey: boolean;
    endpointUrl: string;
  };
}

interface Settings {
  defaultProviderId: string;
  defaultCapabilityLevel: string;
  canEditProviderConfiguration: boolean;
  canEditCapabilityLevel: boolean;
  capabilityLevels: Record<string, string>;
  providers: Provider[];
}

const settings = ref<Settings | null>(null);
const isLoading = ref(false);
const isSaving = ref(false);
const defaultProviderId = ref('');
const defaultCapabilityLevel = ref('');
const providerConfigurations = ref<Record<string, ProviderConfiguration>>({});

const providerOptions = computed(() => {
  const options: Record<string, string> = {};
  const providers = settings.value?.providers || [];

  providers.forEach((provider) => {
    options[provider.id] = provider.name;
  });

  return options;
});

const capabilityLevelOptions = computed(() => {
  const options: Record<string, string> = {};
  Object.entries(settings.value?.capabilityLevels || {}).forEach(([id, translationKey]) => {
    options[id] = translate(translationKey);
  });
  return options;
});

function applySettings(nextSettings: Settings) {
  settings.value = nextSettings;
  defaultProviderId.value = nextSettings.defaultProviderId;
  defaultCapabilityLevel.value = nextSettings.defaultCapabilityLevel;

  const nextProviderConfigurations: Record<string, ProviderConfiguration> = {};
  nextSettings.providers.forEach((provider) => {
    nextProviderConfigurations[provider.id] = {
      apiKey: '',
      endpointUrl: provider.configuration.endpointUrl || '',
    };
  });
  providerConfigurations.value = nextProviderConfigurations;
}

async function loadSettings() {
  isLoading.value = true;

  try {
    const response = await AjaxHelper.fetch<Settings>({
      method: 'AIProviders.getSettings',
    });
    applySettings(response);
  } finally {
    isLoading.value = false;
  }
}

function updateApiKey(providerId: string, apiKey: string) {
  providerConfigurations.value[providerId] = {
    ...providerConfigurations.value[providerId],
    apiKey,
  };
}

function updateEndpointUrl(providerId: string, endpointUrl: string) {
  providerConfigurations.value[providerId] = {
    ...providerConfigurations.value[providerId],
    endpointUrl,
  };
}

async function saveSettings() {
  isSaving.value = true;

  try {
    const response = await AjaxHelper.post<Settings>(
      {
        method: 'AIProviders.saveSettings',
      },
      {
        defaultProviderId: defaultProviderId.value,
        defaultCapabilityLevel: defaultCapabilityLevel.value,
        providerConfigurations: JSON.stringify(providerConfigurations.value),
      },
      {
        withTokenInUrl: true,
      },
    );
    applySettings(response);

    const notificationInstanceId = NotificationsStore.show({
      message: translate('AIProviders_SettingsSaveSuccess'),
      type: 'transient',
      id: 'aiProvidersSettings',
      context: 'success',
    });
    NotificationsStore.scrollToNotification(notificationInstanceId);
  } finally {
    isSaving.value = false;
  }
}

onMounted(loadSettings);
</script>

<template>
  <ContentBlock :content-title="translate('AIProviders_MenuTitle')">
    <ActivityIndicator
      v-if="isLoading"
      :loading="isLoading"
    />

    <div v-else-if="settings" v-form>
      <Field
        uicontrol="select"
        name="defaultProviderId"
        v-model="defaultProviderId"
        :title="translate('AIProviders_DefaultProvider')"
        :options="providerOptions"
      />

      <Field
        uicontrol="select"
        name="defaultCapabilityLevel"
        v-model="defaultCapabilityLevel"
        :title="translate('AIProviders_DefaultCapabilityLevel')"
        :options="capabilityLevelOptions"
        :disabled="!settings.canEditCapabilityLevel"
      />

      <Alert
        v-if="!settings.canEditProviderConfiguration"
        severity="info"
      >
        {{ translate('AIProviders_CloudConfigurationHelp') }}
      </Alert>

      <div v-if="settings.canEditProviderConfiguration">
        <h3>
          {{ translate('AIProviders_ProviderConnections') }}
        </h3>

        <div
          v-for="provider in settings.providers"
          :key="provider.id"
          class="ai-provider"
        >
          <h4>{{ provider.name }}</h4>
          <p class="form-description">{{ translate(provider.description) }}</p>

          <Field
            v-if="provider.supportsCustomEndpoint"
            uicontrol="text"
            :name="`endpointUrl-${provider.id}`"
            :model-value="providerConfigurations[provider.id]?.endpointUrl"
            @update:model-value="updateEndpointUrl(provider.id, `${$event}`)"
            :title="translate('AIProviders_EndpointUrl')"
            :inline-help="translate('AIProviders_EndpointUrlHelp')"
            autocomplete="off"
          />

          <Field
            uicontrol="password"
            :name="`apiKey-${provider.id}`"
            :model-value="providerConfigurations[provider.id]?.apiKey"
            @update:model-value="updateApiKey(provider.id, `${$event}`)"
            :title="translate('AIProviders_ApiKey')"
            :inline-help="provider.configuration.hasApiKey
              ? translate('AIProviders_ApiKeyAlreadyConfigured')
              : translate('AIProviders_ApiKeyHelp')"
            autocomplete="off"
            v-auto-clear-password
          />
        </div>
      </div>

      <SaveButton
        @confirm="saveSettings()"
        :saving="isSaving"
      />
    </div>
  </ContentBlock>
</template>
