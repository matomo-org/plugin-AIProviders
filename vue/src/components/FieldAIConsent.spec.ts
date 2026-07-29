/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { mount } from '@vue/test-utils';

jest.mock('CoreHome', () => ({
  translate: (key: string) => key,
}), { virtual: true });

// eslint-disable-next-line @typescript-eslint/no-var-requires
const FieldAIConsent = require('./FieldAIConsent.vue').default;

const availableValues = {
  general: { key: 'general', value: 'Publicly available information', description: 'Public only.' },
  aggregated: { key: 'aggregated', value: 'Aggregated report data', description: 'Reports.' },
  raw: { key: 'raw', value: 'Visitor level data', description: 'Visits log.' },
};

function mountField(modelValue = 'none') {
  return mount(FieldAIConsent as any, {
    props: {
      availableValues,
      modelValue,
      id: 'dataConsent',
      name: 'dataConsent',
      title: 'Data that AI features may process',
    },
  });
}

function checkboxes(wrapper: ReturnType<typeof mountField>) {
  return wrapper.findAll('input[type="checkbox"]').map((input) => ({
    checked: (input.element as HTMLInputElement).checked,
    disabled: (input.element as HTMLInputElement).disabled,
  }));
}

describe('AIProviders/FieldAIConsent', () => {
  it('offers one checkbox per tier, least sensitive first', () => {
    const wrapper = mountField();

    expect(wrapper.findAll('.fieldAIConsent__tierName').map((n) => n.text())).toEqual([
      'Publicly available information',
      'Aggregated report data',
      'Visitor level data',
    ]);
    expect(wrapper.findAll('.fieldAIConsent__tierHelp').map((n) => n.text())).toEqual([
      'Public only.',
      'Reports.',
      'Visits log.',
    ]);
  });

  it('ticks nothing without consent', () => {
    const wrapper = mountField('none');

    expect(checkboxes(wrapper)).toEqual([
      { checked: false, disabled: false },
      { checked: false, disabled: false },
      { checked: false, disabled: false },
    ]);
    expect(wrapper.findAll('.fieldAIConsent__tierBadge').length).toBe(0);
  });

  it('ticks and locks the tiers included by the consented one', () => {
    const wrapper = mountField('aggregated');

    expect(checkboxes(wrapper)).toEqual([
      // included, so not revocable on its own
      { checked: true, disabled: true },
      { checked: true, disabled: false },
      { checked: false, disabled: false },
    ]);
    expect(wrapper.findAll('.fieldAIConsent__tierBadge').length).toBe(1);
    expect(wrapper.findAll('.fieldAIConsent__tierName--included').length).toBe(1);
  });

  it('consents up to the ticked tier', async () => {
    const wrapper = mountField('none');

    await wrapper.findAll('input[type="checkbox"]')[2].setValue(true);

    expect(wrapper.emitted('update:modelValue')).toEqual([['raw']]);
  });

  it('steps down one tier when the highest ticked tier is unticked', async () => {
    const wrapper = mountField('raw');

    await wrapper.findAll('input[type="checkbox"]')[2].setValue(false);

    expect(wrapper.emitted('update:modelValue')).toEqual([['aggregated']]);
  });

  it('revokes all consent when the only ticked tier is unticked', async () => {
    const wrapper = mountField('general');

    await wrapper.findAll('input[type="checkbox"]')[0].setValue(false);

    expect(wrapper.emitted('update:modelValue')).toEqual([['none']]);
  });
});
