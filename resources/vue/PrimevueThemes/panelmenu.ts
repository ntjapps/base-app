/* eslint-disable @typescript-eslint/no-explicit-any */
export const TRANSITIONS = {
    toggleable: {
        enterFromClass: "max-h-0",
        enterActiveClass:
            "overflow-hidden transition-all duration-500 ease-in-out",
        enterToClass: "max-h-40	",
        leaveFromClass: "max-h-40",
        leaveActiveClass: "overflow-hidden transition-all duration-500 ease-in",
        leaveToClass: "max-h-0",
    },
};

export default {
    root: "w-full",
    panel: "mb-1",
    header: {
        class: [
            "outline-none",
            "focus:outline-none focus:outline-offset-0 focus:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:focus:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]", // Focus
        ],
    },
    headercontent: {
        class: [
            "border border-solid rounded-md transition-shadow duration-200",
            "hover:bg-primary",
        ],
    },
    headeraction: {
        class: [
            "flex items-center select-none cursor-pointer relative no-underline",
            "p-5 font-bold",
        ],
    },
    submenuicon: "mr-2",
    headericon: "mr-2",
    menucontent:
        "py-1 border border-t-0 rounded-t-none rounded-br-md rounded-bl-md",
    menu: {
        class: ["outline-none", "m-0 p-0 list-none"],
    },
    content: ({ context }: { context: any }) => ({
        class: [
            "transition-shadow duration-200 border-none rounded-none",
            "hover:bg-primary", // Hover
            {
                "bg-primary-focus": context.focused,
            },
        ],
    }),
    action: {
        class: [
            "py-3 px-5 select-none",
            "flex items-center cursor-pointer no-underline relative overflow-hidden",
        ],
    },
    icon: "mr-2",
    submenu: "p-0 pl-4 m-0 list-none",
    transition: TRANSITIONS.toggleable,
};
