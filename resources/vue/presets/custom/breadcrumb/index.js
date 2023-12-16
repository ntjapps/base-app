export default {
    root: {
        class: [
            "overflow-x-auto",
            "bg-white dark:bg-gray-900 border border-gray-300 dark:border-blue-900/40 rounded-md p-4",
        ],
    },
    menu: {
        class: "m-0 p-0 list-none flex items-center flex-nowrap",
    },
    action: {
        class: [
            "text-decoration-none flex items-center",
            "transition-shadow duration-200 rounded-md text-gray-600 dark:text-white/70",
            "focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]",
        ],
    },
    icon: {
        class: "text-gray-600 dark:text-white/70",
    },
    separator: {
        class: ["mx-2 text-gray-600 dark:text-white/70", "flex items-center"],
    },
};
