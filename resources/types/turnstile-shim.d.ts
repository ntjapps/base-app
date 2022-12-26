interface Window {
    turnstile: {
        ready: (callback: () => void) => void;
        render: (
            container: string | HTMLElement,
            params: RenderParameters
        ) => void;
        reset: () => void;
    };
    onTurnstileLoad: () => void;
}

interface TurnstileData {
    resetTime?: ReturnType<typeof setTimeout>;
}
