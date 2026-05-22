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

<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';
import {
  ActivityIndicator,
  AjaxHelper,
  Alert,
  ContentBlock,
  NotificationsStore,
  translate,
} from 'CoreHome';
import { Form as vForm, SaveButton } from 'CorePluginsAdmin';
import ProviderCard from './components/ProviderCard.vue';
import type {
  AIProviderResponse,
  CapabilityLevelOption,
  ProviderConfiguration,
  Settings,
} from './types';

const settings = ref<Settings | null>(null);
const isLoading = ref(false);
const isSaving = ref(false);
const defaultProviderId = ref('');
const defaultCapabilityLevel = ref('');
const providerConfigurations = ref<Record<string, ProviderConfiguration>>({});
const testingProviders = ref<Record<string, boolean>>({});
const disconnectingProviders = ref<Record<string, boolean>>({});

const providers = computed(() => settings.value?.providers || []);
const hasUsableProvider = computed(() => providers.value.some(
  (provider) => provider.configuration.isUsable,
));
const canEditCapabilityLevel = computed(() => !!settings.value?.canEditCapabilityLevel);
const canEditProviderConfiguration = computed(() => !!settings.value?.canEditProviderConfiguration);
const selectedProvider = computed(() => providers.value.find((provider) => (
  provider.id === defaultProviderId.value
)));

const capabilityLevelOptions = computed<CapabilityLevelOption[]>(() => {
  const capabilityLevels = settings.value?.capabilityLevels || {};

  return Object.entries(capabilityLevels).map(([id, keys]) => ({
    id,
    label: translate(keys.label),
    description: keys.description ? translate(keys.description) : '',
  }));
});
const selectedCapabilityLevel = computed(() => capabilityLevelOptions.value.find((capability) => (
  capability.id === defaultCapabilityLevel.value
)));
const selectedConfigurationLabel = computed(() => {
  if (!selectedProvider.value || !selectedCapabilityLevel.value) {
    return '';
  }

  return translate(
    'AIProviders_SelectedConfiguration',
    selectedProvider.value.name,
    selectedCapabilityLevel.value.label,
  );
});

/**
 * Applies the given settings to the component state.
 * @param nextSettings
 */
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

function markProviderUsable(providerId: string) {
  if (!settings.value) {
    return;
  }

  const provider = settings.value.providers.find((p) => p.id === providerId);
  if (provider) {
    provider.configuration = {
      ...provider.configuration,
      hasApiKey: true,
      isUsable: true,
    };
  }

  if (!defaultProviderId.value) {
    defaultProviderId.value = providerId;
  }
}

function getCleanErrorMessage(error: unknown) {
  let message = '';

  if (error && typeof error === 'object' && 'message' in error) {
    message = `${(error as { message: string }).message}`;
  } else {
    message = `${error}`;
  }

  return message
    .replace(/\s*#\d+\s+[\s\S]*$/, '')
    .replace(/\s+/g, ' ')
    .trim();
}

function showErrorNotification(error: unknown, id: string) {
  const cleaned = getCleanErrorMessage(error);
  const isUseful = cleaned && cleaned !== 'Something went wrong';
  const message = isUseful
    ? translate('AIProviders_RequestFailed', cleaned)
    : translate('AIProviders_UnexpectedError');

  return NotificationsStore.show({
    message,
    type: 'transient',
    id,
    context: 'error',
  });
}

async function loadSettings() {
  isLoading.value = true;

  try {
    const response = await AjaxHelper.fetch<Settings>({
      method: 'AIProviders.getSettings',
    }, {
      createErrorNotification: false,
    });
    applySettings(response);
  } catch (error) {
    showErrorNotification(error, 'aiProvidersLoadError');
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

async function disconnectProvider(providerId: string) {
  disconnectingProviders.value[providerId] = true;

  try {
    const response = await AjaxHelper.post<Settings>(
      {
        method: 'AIProviders.disconnectProvider',
      },
      {
        providerId,
      },
      {
        withTokenInUrl: true,
        createErrorNotification: false,
      },
    );
    applySettings(response);

    NotificationsStore.show({
      message: translate('AIProviders_DisconnectSuccess'),
      type: 'transient',
      id: `aiProvidersDisconnect-${providerId}`,
      context: 'success',
    });
  } catch (error) {
    showErrorNotification(error, `aiProvidersDisconnectError-${providerId}`);
  } finally {
    disconnectingProviders.value[providerId] = false;
  }
}

async function testConnection(providerId: string) {
  testingProviders.value[providerId] = true;

  try {
    const response = await AjaxHelper.post<AIProviderResponse>(
      {
        method: 'AIProviders.testConnection',
      },
      {
        providerId,
        providerConfiguration: JSON.stringify(providerConfigurations.value[providerId] || {}),
      },
      {
        withTokenInUrl: true,
        createErrorNotification: false,
      },
    );

    markProviderUsable(providerId);

    NotificationsStore.show({
      message: translate(
        'AIProviders_TestConnectionSuccess',
        response.providerName,
        response.text,
      ),
      type: 'transient',
      id: `aiProvidersTest-${providerId}`,
      context: 'success',
    });
  } catch (error) {
    showErrorNotification(error, `aiProvidersTestError-${providerId}`);
  } finally {
    testingProviders.value[providerId] = false;
  }
}

function cancelChanges() {
  if (settings.value) {
    applySettings(settings.value);
  }
}

/**
 * Saves the current settings to the server.
 */
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
        createErrorNotification: false,
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
  } catch (error) {
    const notificationInstanceId = showErrorNotification(error, 'aiProvidersSettingsError');
    NotificationsStore.scrollToNotification(notificationInstanceId);
  } finally {
    isSaving.value = false;
  }
}

onMounted(loadSettings);
</script>

<template>
  <div class="ai-providers-page">
    <header class="ai-providers-page-header">
      <h2 class="ai-providers-page-title">{{ translate('AIProviders_MenuTitle') }}</h2>
      <p class="ai-providers-page-subtitle">
        {{ translate('AIProviders_ConfigurationIntro') }}
      </p>
      <span
        v-if="selectedConfigurationLabel"
        class="ai-providers-selected-configuration"
      >
        {{ selectedConfigurationLabel }}
      </span>
    </header>

    <ActivityIndicator
      v-if="isLoading"
      :loading="isLoading"
    />

    <ContentBlock v-else-if="settings">
      <div v-form class="ai-providers">
        <Alert
          v-if="!canEditProviderConfiguration"
          severity="info"
        >
          {{ translate('AIProviders_CloudConfigurationHelp') }}
        </Alert>

        <h3 class="ai-providers-defaults-title">
          {{ translate('AIProviders_DefaultsTitle') }}
        </h3>

        <section class="ai-providers-section">
          <h4 class="ai-providers-subsection-title">
            {{ translate('AIProviders_DefaultProvider') }}
          </h4>
          <p class="ai-providers-section-help">
            {{ translate('AIProviders_DefaultProviderHelp') }}
          </p>

          <div
            :aria-label="translate('AIProviders_DefaultProvider')"
            class="ai-providers-cards"
            role="radiogroup"
          >
            <ProviderCard
              v-for="provider in providers"
              :key="provider.id"
              :can-edit="canEditProviderConfiguration"
              :configuration="providerConfigurations[provider.id]"
              :is-disconnecting="!!disconnectingProviders[provider.id]"
              :is-testing="!!testingProviders[provider.id]"
              :provider="provider"
              :selected="defaultProviderId === provider.id"
              :usable-as-default="provider.configuration.isUsable"
              @disconnect="disconnectProvider(provider.id)"
              @select="provider.configuration.isUsable ? defaultProviderId = provider.id : null"
              @test="testConnection(provider.id)"
              @update:api-key="updateApiKey(provider.id, $event)"
              @update:endpoint-url="updateEndpointUrl(provider.id, $event)"
            />
          </div>

          <Alert
            v-if="!hasUsableProvider"
            class="ai-providers-default-warning"
            severity="warning"
          >
            {{ translate('AIProviders_NoDefaultProviderWarning') }}
          </Alert>
        </section>

        <section
          v-if="canEditCapabilityLevel"
          class="ai-providers-section"
        >
          <h4 class="ai-providers-subsection-title">
            {{ translate('AIProviders_DefaultCapabilityLevel') }}
          </h4>
          <p class="ai-providers-section-help">
            {{ translate('AIProviders_DefaultCapabilityLevelHelp') }}
          </p>

          <div
            :aria-label="translate('AIProviders_DefaultCapabilityLevel')"
            class="ai-providers-capability-cards"
            role="radiogroup"
          >
            <label
              v-for="capability in capabilityLevelOptions"
              :key="capability.id"
              :class="{ 'is-selected': defaultCapabilityLevel === capability.id }"
              class="ai-providers-capability-card"
            >
              <div class="ai-providers-capability-header">
                <input
                  v-model="defaultCapabilityLevel"
                  :value="capability.id"
                  name="defaultCapabilityLevel"
                  type="radio"
                />
                <span class="ai-providers-capability-label">{{ capability.label }}</span>
              </div>
              <div
                v-if="capability.description"
                class="ai-providers-capability-description"
              >
                {{ capability.description }}
              </div>
            </label>
          </div>
        </section>
      </div>
    </ContentBlock>

    <div
      v-if="settings"
      class="ai-providers-footer"
    >
      <button
        :disabled="isSaving"
        class="btn btn-outline"
        type="button"
        @click="cancelChanges()"
      >
        {{ translate('General_Cancel') }}
      </button>
      <SaveButton
        :saving="isSaving"
        @confirm="saveSettings()"
      />
    </div>
  </div>
</template>

<style lang="less">
.ai-providers-page {
  --ai-providers-border: var(--theme-color-border-light, #e0e0e0);
  --ai-providers-border-strong: var(--theme-color-border, #ccc);
  --ai-providers-accent: var(--theme-color-brand, #43a047);
  --ai-providers-text-muted: var(--theme-color-text-light, #666);
  --ai-providers-heading: var(--theme-color-headline-alternative, #333);

  h2, h3, h4 {
    color: var(--ai-providers-heading);
    margin: 0;
    padding: 0;
  }
}

.ai-providers-page-header {
  margin-bottom: 24px;
}

.ai-providers-page-title {
  font-size: 22px;
  line-height: 1.3;
}

.ai-providers-page-subtitle {
  color: var(--ai-providers-text-muted);
  font-size: 14px;
  line-height: 1.5;
  margin: 4px 0 0;
}

.ai-providers-selected-configuration {
  display: inline-block;
  margin-top: 8px;
  height: 24px;
  padding: 0 10px;
  border-radius: 12px;
  background-color: #e4e4e4;
  color: rgba(0, 0, 0, 0.6);
  line-height: 24px;
  font-size: 12px;
  font-weight: 500;
}

.ai-providers-defaults-title {
  font-size: 18px;
  line-height: 1.4;
  margin-bottom: 24px!important;
}

.ai-providers-subsection-title {
  font-size: 15px;
  font-weight: 600;
  line-height: 1.4;
}

.ai-providers-section + .ai-providers-section {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #ccc;
}

.ai-providers-section-help {
  color: var(--ai-providers-text-muted);
  margin: 4px 0 16px;
}

.ai-providers-cards,
.ai-providers-capability-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

.ai-providers-cards {
  padding-top: 24px;
  margin-top: 32px;
}

.ai-providers-default-warning {
  margin-top: 16px;
}

.ai-providers-capability-card {
  display: flex;
  flex-direction: column;
  padding: 16px;
  background: var(--theme-color-background-contrast, #fff);
  border: 1px solid var(--ai-providers-border);
  border-radius: 6px;
  cursor: pointer;
  transition: border-color 120ms ease, box-shadow 120ms ease, background-color 120ms ease;

  &:hover {
    border-color: var(--ai-providers-border-strong);
  }

  &.is-selected {
    border-color: var(--ai-providers-accent);
    box-shadow: 0 0 0 1px var(--ai-providers-accent) inset;
    background: var(--theme-color-background-tinyContrast, #f7f7f7);
  }
}

.ai-providers-capability-header {
  margin-bottom: 8px;
}

.ai-providers-capability-label {
  color: var(--ai-providers-heading);
  font-weight: 600;
  font-size: 15px;
}

.ai-providers-capability-description {
  color: var(--ai-providers-text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.ai-providers-footer {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  margin-top: 24px;
}
</style>
