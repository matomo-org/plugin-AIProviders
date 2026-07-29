<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="fieldAIConsent">
    <label class="fieldAIConsent__title" v-show="title">{{ title }}</label>

    <p
      class="fieldAIConsent__tier"
      v-for="(tier, index) in tiers"
      :key="tier.key"
    >
      <span class="fieldAIConsent__tierHead">
        <label class="fieldAIConsent__tierLabel">
          <input
            type="checkbox"
            :id="`${id}${tier.key}`"
            :name="name"
            :value="tier.key"
            :checked="index <= selectedIndex"
            :disabled="index < selectedIndex"
            :aria-describedby="`${id}${tier.key}Help`"
            @change="onToggle(index)"
          />
          <span
            class="fieldAIConsent__tierName"
            :class="{ 'fieldAIConsent__tierName--included': index < selectedIndex }"
          >{{ tier.value }}</span>
        </label>

        <span
          class="fieldAIConsent__tierBadge"
          v-if="index < selectedIndex"
        >{{ translate('AIProviders_DataConsentIncluded') }}</span>
      </span>

      <span
        class="fieldAIConsent__tierHelp"
        :id="`${id}${tier.key}Help`"
        v-show="tier.description"
      >{{ tier.description }}</span>
    </p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { translate } from 'CoreHome';

/** Mirrors AIDataScope::NONE. */
const NO_CONSENT = 'none';

interface ConsentTier {
  key: string;
  value: string;
  description?: string;
}

/**
 * Checklist for the AI data consent setting. The stored value is the highest
 * ticked tier: ticking one ticks and disables the tiers below it, unticking the
 * highest one steps down a tier.
 */
export default defineComponent({
  props: {
    modelValue: String,
    title: String,
    name: String,
    id: String,
    availableValues: Object,
  },
  inheritAttrs: false,
  emits: ['update:modelValue'],
  computed: {
    tiers(): ConsentTier[] {
      return Object.values(this.availableValues || {}) as ConsentTier[];
    },
    /** Highest ticked tier, -1 when nothing is consented to. */
    selectedIndex(): number {
      return this.tiers.findIndex((tier) => tier.key === this.modelValue);
    },
  },
  methods: {
    translate,
    onToggle(index: number) {
      // unticking the highest tier keeps the one below rather than revoking all
      const newValue = index === this.selectedIndex
        ? (this.tiers[index - 1]?.key ?? NO_CONSENT)
        : this.tiers[index].key;

      this.$emit('update:modelValue', newValue);
    },
  },
});
</script>

<style lang="less">
.fieldAIConsent {
  // width Materialize reserves for the checkbox, so text lines up under the label
  @_labelIndent: 35px;

  .fieldAIConsent__title {
    display: inline-block;
    padding-bottom: 10px;
    color: var(--theme-color-text-light);
    font-size: 13px;
  }

  .fieldAIConsent__tier {
    margin: 0 0 14px;
  }

  .fieldAIConsent__tierHead {
    display: flex;
    align-items: safe center;
    gap: 8px;
  }

  .fieldAIConsent__tierName {
    color: var(--theme-color-text);
  }

  .fieldAIConsent__tierName--included {
    color: var(--theme-color-text-light);
  }

  .fieldAIConsent__tierBadge {
    padding: 0 6px;
    border-radius: 3px;
    background: var(--theme-color-background-tinyContrast);
    color: var(--theme-color-text-light);
    font-size: 11px;
    line-height: 18px;
    white-space: nowrap;
  }

  .fieldAIConsent__tierHelp {
    display: block;
    padding-left: @_labelIndent;
    color: var(--theme-color-text-light);
    font-size: 13px;
    line-height: 1.5;
  }
}
</style>
