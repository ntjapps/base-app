import { describe, it, expect, beforeAll, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpToast from './CmpToast.vue';

describe('CmpToast.vue', () => {
    const add = vi.fn();

    beforeAll(() => {
        vi.stubGlobal('useToast', () => ({ add }));
    });

    it('mounts and exposes toastDisplay', () => {
        const wrapper = mount(CmpToast);
        expect(wrapper.exists()).toBe(true);

        expect(typeof (wrapper.vm as any).toastDisplay).toBe('function');
    });

    it('renders non-error toast payloads', () => {
        add.mockClear();
        const wrapper = mount(CmpToast);
        (wrapper.vm as any).toastDisplay({ severity: 'success', title: 'T', detail: 'D' });
        expect(add).toHaveBeenCalledWith({
            color: 'success',
            title: 'T',
            description: 'D',
            icon: 'i-lucide-bell-ring',
        });

        add.mockClear();
        (wrapper.vm as any).toastDisplay({ severity: 'warn', summary: 'S', detail: 'D2' });
        expect(add).toHaveBeenCalledWith({
            color: 'warning',
            title: 'S',
            description: 'D2',
            icon: 'i-lucide-bell-ring',
        });
    });

    it('handles axios-like errors passed directly', () => {
        add.mockClear();
        const wrapper = mount(CmpToast);
        (wrapper.vm as any).toastDisplay({
            isAxiosError: true,
            message: 'Request failed with status code 422',
            response: {
                status: 422,
                data: { message: 'Invalid', errors: { a: ['x', 'y'] } },
            },
        });

        expect(add).toHaveBeenCalledWith({
            color: 'error',
            title: 'Invalid',
            description: 'x\ny',
            icon: 'i-lucide-ban',
        });
    });

    it('handles severity error payload containing axios-like response', () => {
        add.mockClear();
        const wrapper = mount(CmpToast);
        (wrapper.vm as any).toastDisplay({
            severity: 'error',
            response: {
                response: {
                    status: 404,
                    data: { message: 'Missing' },
                },
            },
        });

        expect(add).toHaveBeenCalledWith({
            color: 'error',
            title: 'Unknown Error',
            description: 'Missing',
            icon: 'i-lucide-ban',
        });
    });

    it('handles errors without response', () => {
        add.mockClear();
        const wrapper = mount(CmpToast);
        (wrapper.vm as any).toastDisplay({
            isAxiosError: true,
            response: undefined,
        });

        expect(add).toHaveBeenCalledWith({
            color: 'error',
            title: 'Unknown Error',
            description: 'Please contact the administrator',
            icon: 'i-lucide-ban',
        });
    });

    it('handles common http statuses', () => {
        const wrapper = mount(CmpToast);

        add.mockClear();
        (wrapper.vm as any).toastDisplay({
            isAxiosError: true,
            response: { status: 500, data: {} },
        });
        expect(add).toHaveBeenCalledWith({
            color: 'error',
            title: 'Server Error',
            description: 'Please contact the administrator',
            icon: 'i-lucide-ban',
        });

        add.mockClear();
        (wrapper.vm as any).toastDisplay({
            isAxiosError: true,
            response: { status: 401, data: {} },
        });
        expect(add).toHaveBeenCalledWith({
            color: 'error',
            title: 'Unauthorized',
            description: 'Action not authorized.',
            icon: 'i-lucide-ban',
        });

        add.mockClear();
        (wrapper.vm as any).toastDisplay({
            isAxiosError: true,
            response: { status: 403, data: {} },
        });
        expect(add).toHaveBeenCalledWith({
            color: 'error',
            title: 'Forbidden',
            description: 'Access denied.',
            icon: 'i-lucide-ban',
        });

        add.mockClear();
        (wrapper.vm as any).toastDisplay({
            isAxiosError: true,
            response: { status: 404, data: {} },
        });
        expect(add).toHaveBeenCalledWith({
            color: 'error',
            title: 'Not Found',
            description: 'Resource not found.',
            icon: 'i-lucide-ban',
        });
    });
});
