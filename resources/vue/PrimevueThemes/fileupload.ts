export default {
    input: "hidden",
    buttonbar: {
        class: [
            "flex flex-wrap",
            "bg-gray-50 dark:bg-gray-800 p-5 border border-solid border-gray-300 dark:border-blue-900/40 text-gray-700 dark:text-white/80 rounded-tr-lg rounded-tl-lg gap-2 border-b-0",
        ],
    },
    chooseButton: {
        class: [
            "text-white bg-blue-500 border border-blue-500 p-3 px-5 rounded-md text-base",
            "overflow-hidden relative flex flex-row items-center justify-center",
        ],
    },
    chooseIcon: "mr-2",
    chooseButtonLabel: "flex-1 font-bold",
    uploadbutton: {
        icon: "mr-2",
    },
    cancelbutton: {
        icon: "mr-2",
    },
    content: {
        class: [
            "relative",
            "bg-white dark:bg-gray-900 p-8 border border-gray-300 dark:border-blue-900/40 text-gray-700 dark:text-white/80 rounded-b-lg",
        ],
    },
    file: {
        class: [
            "flex items-center flex-wrap",
            "p-4 border border-gray-300 dark:border-blue-900/40 rounded gap-2 mb-2",
            "last:mb-0",
        ],
    },
    thumbnail: "shrink-0",
    fileName: "mb-2",
    fileSize: "mr-2",
    uploadicon: "mr-2",
};
