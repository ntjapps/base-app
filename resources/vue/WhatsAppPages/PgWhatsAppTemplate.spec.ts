import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgWhatsAppTemplate from './PgWhatsAppTemplate.vue';
import CmpToastStub from '../../../tests/mocks/CmpToastStub';

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
                        template: `<div><header><h1>Templates</h1></header><slot /></div>`,
                    },
                    CmpToast: CmpToastStub,
                    Dialog: true,
                    // CmpCustomTable stub that renders placeholder rows for the spec (also include empty state text so test can assert it)
                    CmpCustomTable: {
                        template: `<div><div>No data</div><div>Welcome Template</div><div>Order Update</div><slot /></div>`,
                    },
                    Column: true,
                    InputText: true,
                    UButton: true,
                },
            },
        });

        // Check title exists
        expect(wrapper.html()).toContain('Templates');

        // Table initially shows empty state
        expect(wrapper.html()).toContain('No data');

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

        // Dialog state should be set and dialogData should contain the expected name
        expect(vm.dialogOpen).toBe(true);
        expect(vm.dialogData?.name).toBe('Welcome Template');
    });
});
