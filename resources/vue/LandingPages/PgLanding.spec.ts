import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import { ref } from 'vue';
import PgLanding from './PgLanding.vue';

// Mock animejs
vi.mock('animejs', () => ({
    animate: vi.fn(() => ({
        play: vi.fn(),
        pause: vi.fn(),
    })),
    stagger: vi.fn(),
}));

// Mock the main store
vi.mock('../AppState', () => ({
    useMainStore: () => ({
        appName: ref('NTJ'),
    }),
}));

// Store original window
const originalWindow = window;

describe('PgLanding', () => {
    let wrapper: ReturnType<typeof mount>;
    let mockScrollTo: ReturnType<typeof vi.fn>;
    let mockAddEventListener: ReturnType<typeof vi.fn>;
    let scrollCallback: (() => void) | undefined;

    // Mock IntersectionObserver
    const mockIntersectionObserver = vi.fn();
    mockIntersectionObserver.mockReturnValue({
        observe: vi.fn(),
        unobserve: vi.fn(),
        disconnect: vi.fn(),
    });
    vi.stubGlobal('IntersectionObserver', mockIntersectionObserver);

    beforeEach(() => {
        vi.resetAllMocks();
        const pinia = createPinia();
        setActivePinia(pinia);

        mockScrollTo = vi.fn();
        mockAddEventListener = vi.fn((event, cb) => {
            if (event === 'scroll') {
                scrollCallback = cb as () => void;
            }
        });

        Object.defineProperty(window, 'scrollTo', {
            value: mockScrollTo,
            writable: true,
        });

        Object.defineProperty(window, 'addEventListener', {
            value: mockAddEventListener,
            writable: true,
        });

        vi.stubGlobal('scrollY', 0);

        wrapper = mount(PgLanding, {
            global: {
                stubs: {
                    UIcon: true, // Stub UIcon to avoid rendering issues
                },
            },
        });
    });

    afterEach(() => {
        Object.defineProperty(window, 'scrollTo', {
            value: originalWindow.scrollTo,
            writable: true,
        });
        Object.defineProperty(window, 'addEventListener', {
            value: originalWindow.addEventListener,
            writable: true,
        });
    });

    it('renders correctly', () => {
        expect(wrapper.exists()).toBe(true);
        expect(wrapper.find('header').exists()).toBe(true);
        expect(wrapper.find('#home').exists()).toBe(true);
        expect(wrapper.find('#solutions').exists()).toBe(true);
        expect(wrapper.find('#services').exists()).toBe(true);
        expect(wrapper.find('#process').exists()).toBe(true);
        expect(wrapper.find('#contact').exists()).toBe(true);
    });

    it('displays the app name in header', () => {
        const span = wrapper.find('header a span.text-xl');
        expect(span.text()).toContain('NTJ'); // matches mocked appName
    });

    it('starts with mobile menu closed', () => {
        const mobileMenu = wrapper.find('.md\\:hidden.bg-white.border-t');
        expect(mobileMenu.isVisible()).toBe(false);
    });

    it('toggles mobile menu when button is clicked', async () => {
        const menuButton = wrapper.find('button.md\\:hidden');

        // Click the menu button
        await menuButton.trigger('click');

        // Check state directly
        expect((wrapper.vm as any).mobileMenuOpen).toBe(true);

        // Wait for potential transition
        await wrapper.vm.$nextTick();

        // Check visibility
        const mobileMenu = wrapper.find('.md\\:hidden.bg-white.border-t');
        expect(mobileMenu.isVisible()).toBe(true);

        // Click again
        await menuButton.trigger('click');
        await wrapper.vm.$nextTick();

        expect((wrapper.vm as any).mobileMenuOpen).toBe(false);
        // Check style directly because isVisible() can be flaky with v-show in test environment sometimes
        // Note: Nuxt UI's v-show simply toggles display: none
        expect(mobileMenu.attributes('style')).toContain('display: none');
    });

    it('calls scrollIntoView when navigation link is clicked', async () => {
        const mockScrollIntoView = vi.fn();
        const mockGetElement = vi.fn().mockReturnValue({
            scrollIntoView: mockScrollIntoView,
        });
        document.getElementById = mockGetElement;

        const link = wrapper.find('nav.hidden.md\\:flex a[href="#solutions"]');
        expect(link.exists()).toBe(true);
        await link.trigger('click');

        expect(mockGetElement).toHaveBeenCalledWith('solutions');
        expect(mockScrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth' });
    });

    it('renders the featured solutions section correctly with new titles', () => {
        const solutionsSection = wrapper.find('#solutions');
        const cards = solutionsSection.findAll('.grid > div');
        expect(cards.length).toBe(3);

        const titles = solutionsSection.findAll('h3');
        expect(titles[0].text()).toBe('Scalable SaaS Platform');
        expect(titles[1].text()).toBe('Enterprise Operations Dashboard');
        expect(titles[2].text()).toBe('Secure Data Management System');
    });

    it('renders the process section with animation classes', () => {
        // Redefine wrapper locally if needed, or better, just reuse the one from beforeEach
        // But since we want to check specific elements, using the existing wrapper is fine.
        const processSection = wrapper.find('#process');
        expect(processSection.exists()).toBe(true);

        const steps = processSection.findAll('.animate-on-scroll');
        expect(steps.length).toBe(4);
    });

    it('renders the services section with animation classes', () => {
        const servicesSection = wrapper.find('#services');
        expect(servicesSection.exists()).toBe(true);

        const cards = servicesSection.findAll('.animate-on-scroll');
        expect(cards.length).toBe(4);
    });
});
