export default {
    root: {
        class: [
            // Sizes
            "w-full",
        ],
    },
    panel: {
        class: ["mb-1"],
    },
    header: {
        class: [
            // Outlines
            "outline-none",
            // Focus
            "focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]",
        ],
    },
    headercontent: {
        class: [
            // Borders & Colors
            "border border-solid border-gray-300 dark:border-blue-900/40 text-gray-700 dark:text-white/80 bg-gray-100 dark:bg-gray-900 rounded-md transition-shadow duration-200",
            // States
            "hover:bg-gray-200 dark:hover:bg-gray-800/80  hover:text-gray-700 dark:hover:text-white/80",
        ],
    },
    headeraction: {
        class: [
            // Layout
            "flex items-center select-none cursor-pointer relative no-underline",
            // Colors
            "text-gray-700 dark:text-white/80 p-5 font-bold",
        ],
    },
    submenuicon: {
        class: ["mr-2"],
    },
    headericon: {
        class: ["mr-2"],
    },
    menucontent: {
        class: "py-1 border border-t-0 border-gray-300 dark:border-blue-900/40 bg-white dark:bg-gray-900 text-gray-700 dark:text-white/80 rounded-t-none rounded-br-md rounded-bl-md",
    },
    menu: {
        class: ["outline-none", "m-0 p-0 list-none"],
    },
    content: ({ context }) => ({
        class: [
            // Colors
            "text-gray-700 dark:text-white/80 border-none rounded-none",
            {
                "bg-gray-300 text-gray-700 dark:text-white/80 dark:bg-gray-800/90":
                    context.focused,
            },
            // Transitions
            "transition-shadow",
            "duration-200",
            // States
            "hover:bg-gray-200 dark:hover:bg-gray-800/80  hover:text-gray-700 dark:hover:text-white/80",
        ],
    }),
    action: {
        class: [
            "text-gray-700 dark:text-white/80 py-3 px-5 select-none",
            "flex items-center cursor-pointer no-underline relative overflow-hidden",
        ],
    },
    icon: {
        class: ["mr-2"],
    },
    submenu: {
        class: ["p-0 pl-4 m-0 list-none"],
    },
    transition: {
        enterFromClass: "max-h-0",
        enterActiveClass:
            "overflow-hidden transition-all duration-500 ease-in-out",
        enterToClass: "max-h-40	",
        leaveFromClass: "max-h-40",
        leaveActiveClass: "overflow-hidden transition-all duration-500 ease-in",
        leaveToClass: "max-h-0",
    },
};
