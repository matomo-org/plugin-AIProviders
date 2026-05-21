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
import { computed } from 'vue';
import { AutoClearPassword as vAutoClearPassword, translate } from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import type { Provider, ProviderConfiguration } from '../types';

const props = defineProps<{
  provider: Provider;
  configuration: ProviderConfiguration | undefined;
  selected: boolean;
  canEdit: boolean;
}>();

/* eslint-disable func-call-spacing, no-spaced-func */
const emit = defineEmits<{
  (e: 'select'): void;
  (e: 'update:apiKey', value: string): void;
  (e: 'update:endpointUrl', value: string): void;
  (e: 'test'): void;
  (e: 'disconnect'): void;
}>();
/* eslint-enable func-call-spacing, no-spaced-func */

const hasPendingKey = computed(() => (props.configuration?.apiKey ?? '') !== '');
</script>

<template>
  <label
    :class="{ 'is-selected': selected }"
    class="ai-providers-card"
  >
    <div class="ai-providers-card-header">
      <input
        :checked="selected"
        :value="provider.id"
        name="defaultProviderId"
        type="radio"
        @change="emit('select')"
      />
      <span class="ai-providers-card-name">{{ provider.name }}</span>
    </div>

    <p class="ai-providers-card-description">
      {{ translate(provider.description) }}
    </p>

    <template v-if="canEdit">
      <Field
        v-if="provider.supportsCustomEndpoint"
        :model-value="configuration?.endpointUrl"
        :name="`endpointUrl-${provider.id}`"
        :title="translate('AIProviders_EndpointUrl')"
        :placeholder="translate('AIProviders_EndpointUrlPlaceholder')"
        autocomplete="off"
        full-width
        uicontrol="text"
        @update:model-value="emit('update:endpointUrl', `${$event}`)"
      />

      <Field
        v-auto-clear-password
        :model-value="configuration?.apiKey"
        :name="`apiKey-${provider.id}`"
        :placeholder="provider.configuration.hasApiKey
          ? translate('AIProviders_ApiKeyAlreadyConfiguredPlaceholder')
          : translate('AIProviders_ApiKeyPlaceholder')"
        :title="translate('AIProviders_ApiKey')"
        autocomplete="new-password"
        full-width
        uicontrol="password"
        @update:model-value="emit('update:apiKey', `${$event}`)"
      />

      <div
        :class="{ 'is-connected': provider.configuration.hasApiKey }"
        class="ai-providers-card-status"
      >
        <span
          aria-hidden="true"
          class="icon ai-providers-status-icon"
          :class="provider.configuration.hasApiKey ? 'icon-ok' : 'icon-minus'"
        ></span>
        {{
          provider.configuration.hasApiKey
            ? translate('AIProviders_StatusConnected')
            : translate('AIProviders_StatusNotConnected')
        }}
      </div>

      <div class="ai-providers-card-actions">
        <button
          class="btn btn-disabled btn-small"
          type="button"
          :disabled="!hasPendingKey && !provider.configuration.hasApiKey"
          @click.prevent="emit('test')"
        >
          {{ translate('AIProviders_TestConnection') }}
        </button>
        <button
          class="btn-flat"
          type="button"
          :disabled="!provider.configuration.hasApiKey"
          @click.prevent="emit('disconnect')"
        >
          {{ translate('AIProviders_Disconnect') }}
        </button>
      </div>
    </template>
  </label>
</template>

<style lang="less">
.ai-providers-card {
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
  }

  // Reset Materialize wrapper margins that would otherwise double-stack with
  // our own form-group / card-description spacing.
  .form-group {
    margin-bottom: 12px;
    border: 0;
  }

  .input-field {
    margin-top: 0;
    margin-bottom: 0;
  }

  .matomo-form-field {
    border: 0;
    margin-left: 0;
    margin-right: 0;
    margin-top: 30px;

    > .col {
      padding-left: 0 !important;
      padding-right: 0 !important;
    }
  }

  .input-field > label,
  .input-field.col > label {
    left: 0 !important;
  }

  .input-field > input {
    padding-left: 0;
    margin-left: 0;
    width: 100%;
    box-sizing: border-box;
  }
}

.ai-providers-card-header {
  margin-bottom: 8px;
}

.ai-providers-card-name {
  color: var(--ai-providers-heading);
  font-weight: 600;
  font-size: 15px;
}

.ai-providers-card-description {
  color: var(--ai-providers-text-muted);
  font-size: 13px;
  line-height: 1.5;
  margin: 0 0 16px;
}

.ai-providers-card-status {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 4px 0 0;
  color: var(--ai-providers-text-muted);
  font-size: 13px;
  line-height: 1.5;

  &.is-connected {
    color: var(--ai-providers-accent);

    .ai-providers-status-icon {
      color: var(--ai-providers-accent);
    }
  }
}

.ai-providers-status-icon {
  font-size: 14px;
  line-height: 1;
  color: var(--ai-providers-border-strong);
  flex: none;
}

.ai-providers-card-actions {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: flex-start;
  gap: 12px;
  margin-top: auto;
  padding-top: 16px;

  .btn,
  .btn-flat {
    white-space: nowrap;
  }

  .btn-flat[disabled] {
    pointer-events: none;
    cursor: not-allowed;
    color: var(--theme-color-text-on-disabled);
  }
}
</style>
