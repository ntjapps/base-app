import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PgUserMan from './PgUserMan.vue';
import { api } from '../AppAxios';
import Echo from 'laravel-echo';
import { useEchoStore } from '../AppState';
import CmpToastStub from '../../../tests/mocks/CmpToastStub';

vi.mock('../AppAxios', () => ({
    api: {
        getUserList: vi.fn(),
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    (globalThis as any).Pusher = vi.fn();
    vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
});

describe('PgUserMan.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgUserMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: CmpToastStub,
                    CmpLayout: true,
                    Dialog: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    DialogUserMan: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('should fetch user list when mounted', async () => {
        const mockResponse = { data: [] };
        (api.getUserList as any).mockResolvedValueOnce(mockResponse);

        const wrapper = mount(PgUserMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: CmpToastStub,
                    CmpLayout: true,
                    Dialog: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    DialogUserMan: true,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getUserList).toHaveBeenCalled();
    });

    it('should refresh when a userman.event arrives', async () => {
        // initial fetch
        (api.getUserList as any).mockResolvedValue({ data: [] });

        const pinia = createPinia();
        setActivePinia(pinia);
        const echoStore = useEchoStore();
        const fakeChannel: { listen: (event: string, cb: (...args: any[]) => void) => void } = {
            listen: () => undefined,
        };
        const capturedCb = { current: undefined as undefined | ((...args: any[]) => void) };
        fakeChannel.listen = (_event, cb) => {
            capturedCb.current = cb;
        };
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(PgUserMan, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpToast: CmpToastStub,
                    CmpLayout: true,
                    Dialog: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    DialogUserMan: true,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getUserList).toHaveBeenCalled();

        // Simulate event
        expect(capturedCb.current).toBeDefined();
        const initialCalls = (api.getUserList as any).mock.calls.length;
        capturedCb.current?.();
        await wrapper.vm.$nextTick();

        // Should have called another fetch
        expect((api.getUserList as any).mock.calls.length).toBeGreaterThan(initialCalls);
    });
});
