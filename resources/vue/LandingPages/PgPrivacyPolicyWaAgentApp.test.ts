import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { ref } from 'vue';
import PgPrivacyPolicyWaAgentApp from './PgPrivacyPolicyWaAgentApp.vue';

// Mock the image import
vi.mock('../images/Main Logo.webp', () => ({
    default: 'mocked-logo.webp',
}));

// Mock the main store
vi.mock('../AppState', () => ({
    useMainStore: () => ({
        appName: ref('Test App'),
    }),
}));

describe('PgPrivacyPolicyWaAgentApp', () => {
    it('renders the privacy policy page correctly', async () => {
        // Create Pinia store
        const pinia = createPinia();
        setActivePinia(pinia);

        // Mount the component
        const wrapper = mount(PgPrivacyPolicyWaAgentApp, {
            global: {
                plugins: [pinia],
                stubs: {
                    // Stub any components if needed
                },
            },
        });

        // Check if the main elements are rendered
        expect(wrapper.text()).toContain('Privacy Policy for App by Test App');
        expect(wrapper.text()).toContain('Last Updated: March 5, 2026');
        expect(wrapper.text()).toContain('WhatsApp Business ecosystem');

        // Check for specific sections
        expect(wrapper.text()).toContain('1. Personal Data We Collect and Why');
        expect(wrapper.text()).toContain('2. How We Use Your Personal Data');
        expect(wrapper.text()).toContain('12. Contact Us');

        // Check if the header contains the app name
        const header = wrapper.find('header');
        expect(header.text()).toContain('Test App');

        // Check if the footer is present
        const footer = wrapper.find('footer');
        expect(footer.text()).toContain('Test App');
    });

    it('scroll to top button is hidden initially', () => {
        const pinia = createPinia();
        setActivePinia(pinia);

        const wrapper = mount(PgPrivacyPolicyWaAgentApp, {
            global: {
                plugins: [pinia],
                stubs: {
                    UButton: {
                        template: '<button><slot /></button>',
                        props: ['color', 'class'],
                    },
                },
            },
        });

        // Check that showScrollTop is initially false
        expect(wrapper.vm.showScrollTop).toBe(false);
    });

    it('scroll to top button shows when scrolled', async () => {
        const pinia = createPinia();
        setActivePinia(pinia);

        const wrapper = mount(PgPrivacyPolicyWaAgentApp, {
            global: {
                plugins: [pinia],
                stubs: {
                    UButton: {
                        template: '<button><slot /></button>',
                        props: ['color', 'class'],
                    },
                },
            },
        });

        // Simulate scroll
        Object.defineProperty(window, 'scrollY', { value: 400, writable: true });
        window.dispatchEvent(new Event('scroll'));

        await wrapper.vm.$nextTick();

        // Check that showScrollTop is now true
        expect(wrapper.vm.showScrollTop).toBe(true);
    });
});
