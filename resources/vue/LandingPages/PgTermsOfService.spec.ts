import { describe, it, expect } from 'vitest';
import { nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import PgTermsOfService from '../LandingPages/PgTermsOfService.vue';
import { createPinia } from 'pinia';

describe('PgTermsOfService', () => {
    it('renders the terms of service page correctly', () => {
        const wrapper = mount(PgTermsOfService, {
            global: {
                plugins: [createPinia()],
            },
        });

        // Check if the page title is rendered (allow full or prefixed titles)
        expect(wrapper.find('h1').text()).toContain('Terms');

        // Check for key sections (match headings used in component)
        expect(wrapper.text()).toContain('License to Use');
        expect(wrapper.text()).toContain('Intellectual Property and Ownership');
        expect(wrapper.text()).toContain('Limitation of Liability');
        expect(wrapper.text()).toContain('Termination of Service');
        expect(wrapper.text()).toContain('Governing Law');
        expect(wrapper.text()).toContain('Changes to Terms');
        expect(wrapper.text()).toContain('Contact');

        // Check contact email
        expect(wrapper.text()).toContain('admin@yourdomain.com');
    });

    it('scroll to top button appears on scroll', async () => {
        const wrapper = mount(PgTermsOfService, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    UButton: true,
                },
            },
        });

        // Initially showScrollTop should be false
        expect(wrapper.vm.showScrollTop).toBe(false);

        // Simulate scroll by updating window.scrollY and dispatching a scroll event
        Object.defineProperty(window, 'scrollY', { value: 400, configurable: true });
        window.dispatchEvent(new Event('scroll'));
        await nextTick();

        // After scroll, showScrollTop should be true
        expect(wrapper.vm.showScrollTop).toBe(true);
    });

    it('switches language to Indonesian', async () => {
        const wrapper = mount(PgTermsOfService, {
            global: {
                plugins: [createPinia()],
            },
        });

        const idButton = wrapper.findAll('button').find((btn) => btn.text() === 'ID');
        expect(idButton).toBeDefined();
        await idButton!.trigger('click');

        expect(wrapper.find('h1').text()).toContain('Syarat dan Ketentuan Layanan');
        expect(wrapper.text()).toContain('Hak Kekayaan Intelektual dan Kepemilikan');
    });
});
