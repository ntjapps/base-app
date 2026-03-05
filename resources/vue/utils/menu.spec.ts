import { describe, it, expect } from 'vitest';
import { normalizeMenu } from './menu';

describe('normalizeMenu', () => {
    it('sets value from id or key when not provided', () => {
        const raw = [
            {
                label: 'Parent',
                key: 'parent-key',
                children: [{ label: 'Child', key: 'child-key' }],
            },
        ];

        const normalized = normalizeMenu(raw);
        expect(normalized[0]).toHaveProperty('value', 'parent-key');
        expect(normalized[0].children?.[0]).toHaveProperty('value', 'child-key');
    });

    it('respects explicit value and defaultOpen in raw items', () => {
        const raw = [{ label: 'X', value: 'custom', defaultOpen: true }];

        const normalized = normalizeMenu(raw);
        expect(normalized[0]).toHaveProperty('value', 'custom');
        expect(normalized[0]).toHaveProperty('defaultOpen', true);
    });

    it('normalizes label, href, and target fallbacks', () => {
        const raw = [
            { title: 'T', url: '/u', target: '_blank', items: [{ name: 'N', href: '/h' }] },
        ];

        const normalized = normalizeMenu(raw);
        expect(normalized[0].label).toBe('T');
        expect(normalized[0].href).toBe('/u');
        expect(normalized[0].target).toBe('_blank');
        expect(normalized[0].children?.[0].label).toBe('N');
        expect(normalized[0].children?.[0].href).toBe('/h');
    });

    it('normalizes menu object maps using entry key as id', () => {
        const normalized = normalizeMenu({ a: { label: 'A' } });
        expect(normalized[0].id).toBe('a');
        expect(normalized[0].value).toBe('a');
    });
});
