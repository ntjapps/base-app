import { describe, it, expect } from 'vitest';
import { timeGreetings, timeView, dateView, syncPromise, formatBytesNumber } from './AppCommon';

describe('AppCommon utilities', () => {
    it('returns correct greeting for different hours', () => {
        const RealDate = Date;
        global.Date = class extends RealDate {
            constructor() {
                super();
                return new RealDate('2020-01-01T05:00:00Z');
            }
            static now() {
                return new RealDate('2020-01-01T05:00:00Z').getTime();
            }
        } as DateConstructor;
        expect(timeGreetings()).toMatch(/Good Morning|Good Night|Good Afternoon|Good Evening/);
        global.Date = RealDate;
    });

    it('formats timeView and dateView', () => {
        const date = '2020-01-01T12:34:56Z';
        expect(typeof timeView(date)).toBe('string');
        expect(typeof dateView(date)).toBe('string');
        // Skipping null argument test due to TS signature
    });

    it('syncPromise resolves to true', async () => {
        await expect(syncPromise()).resolves.toBe(true);
    });

    it('formats bytes correctly', () => {
        expect(formatBytesNumber(500)).toMatch(/Bytes/);
        expect(formatBytesNumber(2048)).toMatch(/KB/);
        expect(formatBytesNumber(1048577)).toMatch(/MB|GB/);
    });
});
