export type RawMenuItem = Record<string, unknown>;

export interface NormalizedMenuItem {
    id?: string;
    key?: string;
    /** The "value" property is used by Nuxt UI's NavigationMenu for v-model/default-value */
    value?: string;
    label: string;
    icon?: string;
    href?: string;
    /** When set, forces native browser navigation instead of Vue Router */
    target?: string;
    /** When set, on vertical menus this will auto-open the item by default */
    defaultOpen?: boolean;
    children?: NormalizedMenuItem[];
}

export const normalizeItem = (raw: RawMenuItem | undefined, key?: string): NormalizedMenuItem => {
    const r = raw ?? {};
    const id = r['id'] ?? r['key'] ?? key ?? (r['label'] ? String(r['label']) : undefined);
    const childrenRaw = (r['children'] ?? r['items'] ?? []) as unknown;
    const children = Array.isArray(childrenRaw)
        ? (childrenRaw as RawMenuItem[]).map((c) => normalizeItem(c, undefined))
        : [];

    const value = r['value'] ? String(r['value']) : id ? String(id) : undefined;
    const defaultOpen = r['defaultOpen'] === true;

    return {
        id: id ? String(id) : undefined,
        key: r['key'] ? String(r['key']) : undefined,
        value,
        label: (r['label'] ?? r['title'] ?? r['name'] ?? '') as string,
        icon: r['icon'] ? String(r['icon']) : undefined,
        href: r['href'] ? String(r['href']) : r['url'] ? String(r['url']) : undefined,
        target: r['target'] ? String(r['target']) : undefined,
        defaultOpen: defaultOpen || undefined,
        children: children.length ? children : undefined,
    };
};

export const normalizeMenu = (menuRaw: unknown): NormalizedMenuItem[] => {
    const raw = menuRaw ?? [];
    if (Array.isArray(raw)) {
        return (raw as RawMenuItem[]).map((m) => normalizeItem(m, undefined));
    }

    return Object.entries(raw as Record<string, RawMenuItem>).map(([k, v]) => normalizeItem(v, k));
};
