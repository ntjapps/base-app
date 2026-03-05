import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PgAiModelInstructionMan from './PgAiModelInstructionMan.vue';
import { api } from '../AppAxios';
import Echo from 'laravel-echo';
import { useEchoStore } from '../AppState';
import CmpToastStub from '../../../tests/mocks/CmpToastStub';

vi.mock('../AppAxios', () => ({
    api: {
        getAiModelInstructionList: vi.fn(),
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    (globalThis as any).Pusher = vi.fn();
    vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
});

describe('PgAiModelInstructionMan.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgAiModelInstructionMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: CmpToastStub,
                    CmpLayout: true,
                    Dialog: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    DialogAiModelInstructionMan: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('should fetch instruction list when mounted', async () => {
        const mockResponse = { data: [] };
        (api.getAiModelInstructionList as any).mockResolvedValueOnce(mockResponse);

        const wrapper = mount(PgAiModelInstructionMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: CmpToastStub,
                    CmpLayout: true,
                    Dialog: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    DialogAiModelInstructionMan: true,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getAiModelInstructionList).toHaveBeenCalled();
    });

    it('should refresh when a settings.event Instruction event arrives', async () => {
        // initial fetch
        (api.getAiModelInstructionList as any).mockResolvedValue({ data: [] });

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

        const wrapper = mount(PgAiModelInstructionMan, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpToast: CmpToastStub,
                    CmpLayout: true,
                    Dialog: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    DialogAiModelInstructionMan: true,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getAiModelInstructionList).toHaveBeenCalled();

        // Simulate event
        expect(capturedCb.current).toBeDefined();
        const initialCalls = (api.getAiModelInstructionList as any).mock.calls.length;
        capturedCb.current?.();
        await wrapper.vm.$nextTick();

        // Should have called another fetch
        expect((api.getAiModelInstructionList as any).mock.calls.length).toBeGreaterThan(
            initialCalls,
        );
    });
});
