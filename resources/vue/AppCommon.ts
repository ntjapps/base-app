import { AxiosResponse } from "axios";

function timeGreetings(): string | null {
    const cT = new Date().getHours();
    let gT = null;

    if (cT >= 4 && cT <= 10) {
        gT = "Good Morning ";
    } else if (cT >= 11 && cT <= 14) {
        gT = "Good Afternoon ";
    } else if (cT >= 15 && cT <= 18) {
        gT = "Good Evening ";
    } else if (cT >= 19 && cT <= 23) {
        gT = "Good Night ";
    } else if (cT >= 0 && cT <= 3) {
        gT = "Good Night ";
    }

    return gT;
}

function timeView(data: string | number | Date): string | null {
    if (data === null) {
        return null;
    } else {
        const date = new Date(data);
        return date.toLocaleString("en-UK");
    }
}

function syncPromise(): Promise<boolean> {
    return new Promise((resolve) => {
        resolve(true);
    });
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function fileDownload(response: AxiosResponse<any, any>) {
    const contentDisposition = response.headers["content-disposition"];
    const filename = contentDisposition
        .split(";")[1]
        .trim()
        .split("=")[1]
        .replace(/"/g, "");

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

function formatBytesNumber(bytes: number) {
    /** If size less than 1 KB return Bytes */
    if (bytes < 1024) {
        return (
            bytes.toLocaleString("en-UK", { maximumFractionDigits: 2 }) +
            " Bytes"
        );
    }

    /** If size less than 1 MB return KB */
    if (bytes < 1048576) {
        return (
            (bytes / 1024).toLocaleString("en-UK", {
                maximumFractionDigits: 2,
            }) + " KB"
        );
    }

    /** If size less than 1 GB return MB */
    if (bytes < 1073741824) {
        return (
            (bytes / 1048576).toLocaleString("en-UK", {
                maximumFractionDigits: 2,
            }) + " MB"
        );
    }

    /** If size less than 1 TB return GB */
    if (bytes < 1099511627776) {
        return (
            (bytes / 1073741824).toLocaleString("en-UK", {
                maximumFractionDigits: 2,
            }) + " GB"
        );
    }
}

export type UserDataInterface = {
    id: string;
    name: string;
    username: string;
    created_at: string;
    updated_at: string;
    deleted_at: string;
    user_permission: Array<any>;
    permissions: Array<any>;
    roles: Array<any>;
};

export {
    timeGreetings,
    timeView,
    syncPromise,
    fileDownload,
    formatBytesNumber,
};
