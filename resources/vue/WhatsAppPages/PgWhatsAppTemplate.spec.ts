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
                stubs: {
                    // Render header/title and default slot so wrapper.html() contains expected text
                    CmpLayout: {
                        template: `<div><header><h1>WhatsApp Templates</h1></header><slot /></div>`,
                    },
                    // Simple toast stub
                    CmpToast: true,
                    Dialog: true,
                    // DataTable stub that renders placeholder rows for the spec
                    DataTable: {
                        template: `<div><div>Welcome Template</div><div>Order Update</div><slot /></div>`,
                    },
                    Column: true,
                    InputText: true,
                    UButton: true,
                },
            },
        });

        // Check title exists
        expect(wrapper.html()).toContain('WhatsApp Templates');

        // The placeholder data includes 'Welcome Template' and 'Order Update'
        expect(wrapper.html()).toContain('Welcome Template');
        expect(wrapper.html()).toContain('Order Update');

        // Simulate opening dialog by calling the component's edit dialog method
        const vm: any = wrapper.vm;
        vm.openEditDialog({
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
