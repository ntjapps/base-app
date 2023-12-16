export default {
    root: ({ props }) => ({
        class: [
            "inline-flex relative",
            {
                "opacity-60 select-none pointer-events-none cursor-default":
                    props.disabled,
            },
        ],
    }),
    panel: {
        class: [
            "p-5 bg-white dark:bg-gray-900 text-gray-700 dark:text-white/80 shadow-md rounded-md",
        ],
    },
    meter: {
        class: ["mb-2 bg-gray-300 dark:bg-gray-700 h-3"],
    },
    meterlabel: ({ instance, props }) => ({
        class: [
            "transition-width duration-1000 ease-in-out h-full",
            {
                "bg-red-500":
                    (instance === null ||
                    instance === void 0 ||
                    (_instance$meter = instance.meter) === null ||
                    _instance$meter === void 0
                        ? void 0
                        : _instance$meter.strength) == "weak",
                "bg-orange-500":
                    (instance === null ||
                    instance === void 0 ||
                    (_instance$meter2 = instance.meter) === null ||
                    _instance$meter2 === void 0
                        ? void 0
                        : _instance$meter2.strength) == "medium",
                "bg-green-500":
                    (instance === null ||
                    instance === void 0 ||
                    (_instance$meter3 = instance.meter) === null ||
                    _instance$meter3 === void 0
                        ? void 0
                        : _instance$meter3.strength) == "strong",
            },
            {
                "pr-[2.5rem] ": props.toggleMask,
            },
        ],
    }),
};
