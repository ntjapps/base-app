import { describe, it, expect, vi } from 'vitest';
import {
    timeGreetings,
    timeView,
    dateView,
    syncPromise,
    formatBytesNumber,
    fileDownload,
} from './AppCommon';

describe('AppCommon utilities', () => {
    it('returns correct greeting for different hours', () => {
        const RealDate = Date;
        const setHour = (h: number) => {
            global.Date = class extends RealDate {
                constructor() {
                    super();
                    return new RealDate(`2020-01-01T${String(h).padStart(2, '0')}:00:00Z`);
                }
                static now() {
                    return new RealDate(
                        `2020-01-01T${String(h).padStart(2, '0')}:00:00Z`,
                    ).getTime();
                }
            } as DateConstructor;
        };

        setHour(5);
        expect(timeGreetings()).toBe('Good Morning ');
        setHour(12);
        expect(timeGreetings()).toBe('Good Afternoon ');
        setHour(16);
        expect(timeGreetings()).toBe('Good Evening ');
        setHour(22);
        expect(timeGreetings()).toBe('Good Night ');
        setHour(2);
        expect(timeGreetings()).toBe('Good Night ');

        global.Date = RealDate;
    });

    it('formats timeView and dateView', () => {
        const date = '2020-01-01T12:34:56Z';
        expect(typeof timeView(date)).toBe('string');
        expect(typeof dateView(date)).toBe('string');
        expect(timeView(null as any)).toBeNull();
        expect(dateView(null as any)).toBeNull();
    });

    it('syncPromise resolves to true', async () => {
        await expect(syncPromise()).resolves.toBe(true);
    });

    it('formats bytes correctly', () => {
        expect(formatBytesNumber(500)).toMatch(/Bytes/);
        expect(formatBytesNumber(2048)).toMatch(/KB/);
        expect(formatBytesNumber(1048577)).toMatch(/MB/);
        expect(formatBytesNumber(1073741825)).toMatch(/GB/);
        expect(formatBytesNumber(1099511627775)).toMatch(/GB/);
    });

    it('downloads files from axios responses', () => {
        const createObjectURL = vi.fn(() => 'blob:1');
        const revokeObjectURL = vi.fn();
        (window.URL as any).createObjectURL = createObjectURL;
        (window.URL as any).revokeObjectURL = revokeObjectURL;

        const click = vi.fn();
        const setAttribute = vi.fn();
        const a = { href: '', setAttribute, click } as any;
        vi.spyOn(document, 'createElement').mockReturnValueOnce(a);

        const appendChild = vi.spyOn(document.body, 'appendChild').mockImplementationOnce(() => a);
        const removeChild = vi.spyOn(document.body, 'removeChild').mockImplementationOnce(() => a);

        fileDownload({
            data: new Uint8Array([1, 2, 3]),
            headers: { 'content-disposition': 'attachment; filename="x.txt"' },
        } as any);

        expect(createObjectURL).toHaveBeenCalled();
        expect(setAttribute).toHaveBeenCalledWith('download', 'x.txt');
        expect(appendChild).toHaveBeenCalled();
        expect(click).toHaveBeenCalled();
        expect(removeChild).toHaveBeenCalled();
        expect(revokeObjectURL).toHaveBeenCalledWith('blob:1');

        (document.createElement as any).mockRestore?.();
    });
});
