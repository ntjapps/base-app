export type EchoChannel = {
    listen: (event: string, cb: (...params: unknown[]) => void) => unknown;
};

export type EchoWithMethods = {
    private: (channel: string) => EchoChannel;
    leave: (channel: string) => void;
};
