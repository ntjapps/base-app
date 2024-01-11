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
    const contentDisposition = response.headers['content-disposition'];
    const filename = contentDisposition.split(';')[1].trim().split('=')[1].replace(/"/g, '');

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

export { timeGreetings, timeView, syncPromise, fileDownload };
