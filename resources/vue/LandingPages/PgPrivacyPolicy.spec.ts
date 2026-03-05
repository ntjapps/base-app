import { describe, it, expect } from 'vitest';
import { nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import PgPrivacyPolicy from '../LandingPages/PgPrivacyPolicy.vue';
import { createPinia } from 'pinia';

describe('PgPrivacyPolicy', () => {
    it('renders the privacy policy page correctly', () => {
        const wrapper = mount(PgPrivacyPolicy, {
            global: {
                plugins: [createPinia()],
            },
        });

        // Check if the page title is rendered (allow full or prefixed titles)
        expect(wrapper.find('h1').text()).toContain('Privacy Policy');

        // Check for key sections (match headings used in component)
        expect(wrapper.text()).toContain('Personal Data We Collect');
        expect(wrapper.text()).toContain('How We Use Your Personal Data');
        expect(wrapper.text()).toContain('Data Security');
        expect(wrapper.text()).toContain('Changes to This Policy');
        expect(wrapper.text()).toContain('Contact Us');

        // Check contact email
        expect(wrapper.text()).toContain('admin@yourdomain.com');
    });

    it('scroll to top button appears on scroll', async () => {
        const wrapper = mount(PgPrivacyPolicy, {
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
        const wrapper = mount(PgPrivacyPolicy, {
            global: {
                plugins: [createPinia()],
            },
        });

        const idButton = wrapper.findAll('button').find((btn) => btn.text() === 'ID');
        expect(idButton).toBeDefined();
        await idButton!.trigger('click');

        expect(wrapper.find('h1').text()).toContain('Kebijakan Privasi');
        expect(wrapper.text()).toContain('Data Pribadi yang Kami Kumpulkan');
    });
});
