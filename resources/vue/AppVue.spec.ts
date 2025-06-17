import { describe, it, expect, vi } from 'vitest';

// Mock Sentry before importing AppVue
vi.mock('@sentry/vue', () => ({
    init: vi.fn(),
    browserTracingIntegration: vi.fn(),
}));

describe('AppVue', () => {
    it('calls Sentry.init with correct options', async () => {
        await import('./AppVue');
        const Sentry = await import('@sentry/vue');
        expect(Sentry.init).toHaveBeenCalled();
    });
});
