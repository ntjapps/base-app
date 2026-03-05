import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { ref } from 'vue';
import PgTermsOfServiceWaAgentApp from './PgTermsOfServiceWaAgentApp.vue';

vi.mock('../images/Main Logo.webp', () => ({
    default: 'mocked-logo.webp',
}));

vi.mock('../AppState', () => ({
    useMainStore: () => ({
        appName: ref('Test App'),
    }),
}));

describe('PgTermsOfServiceWaAgentApp', () => {
    it('renders WaAgent terms page content', () => {
        const pinia = createPinia();
        setActivePinia(pinia);

        const wrapper = mount(PgTermsOfServiceWaAgentApp, {
            global: {
                plugins: [pinia],
            },
        });

        expect(wrapper.text()).toContain('Terms of Service for App by Test App');
        expect(wrapper.text()).toContain('Last Updated: March 5, 2026');
        expect(wrapper.text()).toContain('2. Service Scope');
        expect(wrapper.text()).toContain('4. Acceptable Use');
        expect(wrapper.text()).toContain('11. Governing Law');
        expect(wrapper.text()).toContain('admin@yourdomain.com');
    });

    it('switches language to Indonesian', async () => {
        const pinia = createPinia();
        setActivePinia(pinia);

        const wrapper = mount(PgTermsOfServiceWaAgentApp, {
            global: {
                plugins: [pinia],
            },
        });

        const idButton = wrapper.findAll('button').find((btn) => btn.text() === 'ID');
        expect(idButton).toBeDefined();
        await idButton!.trigger('click');

        expect(wrapper.text()).toContain('Syarat dan Ketentuan App oleh Test App');
        expect(wrapper.text()).toContain('4. Penggunaan yang Diperbolehkan');
    });
});
