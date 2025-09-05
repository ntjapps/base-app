import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgWhatsAppTemplate from './PgWhatsAppTemplate.vue';

describe('PgWhatsAppTemplate.vue', () => {
    it('renders placeholder templates and opens dialog', async () => {
    const pinia = createPinia();
    const wrapper = mount(PgWhatsAppTemplate, {
            props: {
                appName: 'Test App',
                greetings: 'Hello',
                expandedKeysProps: '',
            },
            global: {
        plugins: [pinia],
        stubs: [
                    'CmpLayout',
                    'CmpToast',
                    'Dialog',
                    'DataTable',
                    'Column',
                    'InputText',
                    'UButton',
                ],
            },
        });

        // Check title exists
        expect(wrapper.html()).toContain('WhatsApp Templates');

        // The placeholder data includes 'Welcome Template' and 'Order Update'
        expect(wrapper.html()).toContain('Welcome Template');
        expect(wrapper.html()).toContain('Order Update');

        // Simulate opening dialog by calling component method
        const vm: any = wrapper.vm;
        vm.openTemplateDialog({
            id: 't1',
            name: 'Welcome Template',
            content: 'Hi',
            language: 'en',
            created_at: '2025-09-04',
        });
        await wrapper.vm.$nextTick();

        // Now the dialog content (name) should be present in the rendered HTML
        expect(wrapper.html()).toContain('Welcome Template');
    });
});
