// Basic test setup for Vitest + jsdom
// Provide common browser globals and simple mocks to avoid environment errors during unit tests.

// Ensure window.matchMedia exists (some libraries expect it)
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {}, // deprecated
    removeListener: () => {}, // deprecated
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false
  })
});

if (!(globalThis as any).IntersectionObserver) {
  (globalThis as any).IntersectionObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
  };
}

if (!(globalThis as any).requestAnimationFrame) {
  (globalThis as any).requestAnimationFrame = (cb: FrameRequestCallback) =>
    setTimeout(() => cb(Date.now()), 0) as unknown as number;
  (globalThis as any).cancelAnimationFrame = (id: number) => clearTimeout(id);
}

// Basic mock for localStorage/sessionStorage if missing
if (!window.localStorage) {
  const storage: Record<string, string> = {};
  window.localStorage = {
    getItem: (k: string) => storage[k] ?? null,
    setItem: (k: string, v: string) => (storage[k] = String(v)),
    removeItem: (k: string) => delete storage[k],
    clear: () => Object.keys(storage).forEach((k) => delete storage[k])
  } as unknown as Storage;
}

// Mock Pusher/Echo if present to avoid network calls
// Some tests directly import Echo or pusher-js; provide a minimal stub.

globalThis.Pusher = globalThis.Pusher || (class DummyPusher {
  constructor() {}
  subscribe() { return { bind: () => {} }; }
  // provide minimal interface used by Echo
});

// Mock reka-ui Tooltip provider components to avoid injection errors
import { vi } from 'vitest';
import { defineComponent } from 'vue';
;(globalThis as any).useToast = () => ({ add: vi.fn() });

vi.mock('axios', () => {
  const instance = {
    defaults: { headers: { common: {} }, withCredentials: true },
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
    get: vi.fn(() => Promise.resolve({ data: {}, status: 200, statusText: 'OK', headers: {}, config: {} })),
    post: vi.fn(() => Promise.resolve({ data: {}, status: 200, statusText: 'OK', headers: {}, config: {} })),
    put: vi.fn(() => Promise.resolve({ data: {}, status: 200, statusText: 'OK', headers: {}, config: {} })),
    patch: vi.fn(() => Promise.resolve({ data: {}, status: 200, statusText: 'OK', headers: {}, config: {} })),
    delete: vi.fn(() => Promise.resolve({ data: {}, status: 200, statusText: 'OK', headers: {}, config: {} })),
  } as any;
  (globalThis as any).__axiosMock = instance;
  return { default: { create: vi.fn(() => instance) }, create: vi.fn(() => instance) };
});

vi.mock('reka-ui', () => ({
  TooltipRoot: defineComponent({ template: '<div><slot /></div>' }),
  Tooltip: defineComponent({ template: '<div><slot /></div>' }),
  // export a noop provider factory so components that rely on context do not throw
  TooltipProvider: defineComponent({ template: '<div><slot /></div>' }),
}));
// Also mock deep imports used by bundler/runtime and the shared createContext used by reka-ui
vi.mock('reka-ui/dist/Tooltip/TooltipRoot', () => ({
  default: defineComponent({ template: '<div><slot /></div>' }),
}));
vi.mock('reka-ui/dist/Tooltip/TooltipRoot.js', () => ({
  default: defineComponent({ template: '<div><slot /></div>' }),
}));
vi.mock('reka-ui/dist/Tooltip/Tooltip', () => ({
  default: defineComponent({ template: '<div><slot /></div>' }),
}));
vi.mock('reka-ui/dist/Tooltip/Tooltip.js', () => ({
  default: defineComponent({ template: '<div><slot /></div>' }),
}));
vi.mock('reka-ui/dist/shared/createContext', () => ({
  // Ensure injectContext doesn't throw in tests
  injectContext: () => ({/* noop context */}),
  createContext: () => ({/* noop create */}),
}));
vi.mock('reka-ui/dist/shared/createContext.js', () => ({
  injectContext: () => ({/* noop context */}),
  createContext: () => ({/* noop create */}),
}));

// Minimal Echo stub that won't try to open network connections
(globalThis as any).Echo = (opts: any) => {
  const instance: any = {
    connect: () => undefined,
    private: (_channel: string) => ({
      listen: (_event: string, _cb: any) => {},
      stopListening: (_event?: string) => {},
      error: (_cb: any) => {}
    }),
    leave: (_channel: string) => {},
  };
  // ensure prototype methods exist for spying
  (instance as any).__proto__ = (globalThis as any).Echo?.prototype || {};
  return instance;
};

// As an extra safeguard, try to patch the real reka-ui createContext module at runtime
// to make injectContext a noop so TooltipRoot won't throw when used in tests.
try {
  // eslint-disable-next-line @typescript-eslint/no-var-requires
  const realCreateContext = require('reka-ui/dist/shared/createContext.js');
  if (realCreateContext && typeof realCreateContext.injectContext === 'function') {
    realCreateContext.injectContext = () => ({ /* noop */ });
  }
} catch (e) {
  // ignore if module cannot be found/required in the test environment
}

// make sure connect exists on prototype for spies
if (!(globalThis as any).Echo.prototype) {
  (globalThis as any).Echo.prototype = { connect: () => undefined, private: () => ({ listen: () => {} }) };
}

// Also mock the laravel-echo module so imports in components get the stubbed class
vi.mock('laravel-echo', () => {
  return {
    default: class Echo {
      constructor(opts?: any) {}
      connect() { return undefined }
      private(_channel: string) { return { listen: (_event: string, _cb: any) => {}, stopListening: () => {}, error: () => {} } }
      leave(_channel: string) { return undefined }
    }
  }
});

// Mock pusher-js module as well so Echo's underlying connector does not throw
vi.mock('pusher-js', () => {
  return class DummyPusher {
    constructor() {}
    subscribe() { return { bind: () => {}, trigger: () => {} } }
    connect() {}
  }
});

vi.mock('@nuxt/ui/vue-plugin', () => ({
  default: {
    install: () => undefined,
  },
}));

vi.mock('@unhead/vue', () => ({
  useHead: () => undefined,
  useSeoMeta: () => undefined,
  createHead: () => ({
    install: () => undefined,
  }),
}));


// Register lightweight global stubs for UI providers to avoid mounting full implementations
import { config } from '@vue/test-utils';
import { defineComponent } from 'vue';

config.global.components = {
  TooltipRoot: defineComponent({ template: '<div><slot /></div>' }),
  Tooltip: defineComponent({ template: '<div><slot /></div>' }),
  UApp: defineComponent({ template: '<div><slot /></div>' }),
  UBadge: defineComponent({ template: '<div><slot /></div>' }),
  UButton: defineComponent({ template: '<button><slot /></button>' }),
  UCard: defineComponent({ template: '<div><slot /></div>' }),
  UCheckbox: defineComponent({ template: '<input />' }),
  UIcon: defineComponent({ template: '<span />' }),
  UInput: defineComponent({ template: '<input />' }),
  UModal: defineComponent({ template: '<div><slot /></div>' }),
  UNavigationMenu: defineComponent({ template: '<nav><slot /></nav>' }),
  UPagination: defineComponent({ template: '<div><slot /></div>' }),
  USelectMenu: defineComponent({ template: '<div><slot /></div>' }),
  USwitch: defineComponent({ template: '<input />' }),
  UTextarea: defineComponent({ template: '<textarea />' }),
  UTooltip: defineComponent({ template: '<div><slot /></div>' }),
};
// Ensure global stubs also override local registrations in tests
config.global.stubs = {
  TooltipRoot: true,
  Tooltip: true,
  UApp: true,
  UBadge: true,
  UButton: true,
  UCard: true,
  UCheckbox: true,
  UIcon: true,
  UInput: true,
  UModal: true,
  UNavigationMenu: true,
  UPagination: true,
  USelectMenu: true,
  USwitch: true,
  UTextarea: true,
  UTooltip: true,
};

// Silence console warnings in tests by default (optional)
// console.warn = () => {};
