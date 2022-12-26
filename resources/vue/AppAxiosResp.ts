import Swal from "@nathanaelytj/sweetalert2/dist/sweetalert2.all";

export interface Response {
    data: {
        title?: string;
        message?: string;
        redirect?: string;
    };
}

export function useResponse(response: Response) {
    return Swal.fire({
        title: response.data.title,
        text: response.data.message,
        timer: 3000,
        icon: "success",
        timerProgressBar: true,
        showConfirmButton: true,
        willClose: () => {
            if (typeof response.data.redirect !== "undefined") {
                window.location.assign(response.data.redirect);
            }
        },
    });
}

export interface Error {
    response: {
        data: {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            errors?: any;
            redirect?: string;
        };
        status?: string;
    };
}

export function useError(error: Error) {
    const eobj = error.response.data.errors;
    const eobs = error.response.status;
    if (typeof eobj !== "undefined") {
        let msg = "";
        for (const value in eobj) {
            msg += eobj[value] + " ";
        }
        Swal.fire({
            title: "Error!!!",
            text: msg,
            timer: 300000,
            icon: "error",
            timerProgressBar: true,
            showConfirmButton: true,
            willClose: () => {
                if (typeof error.response.data.redirect !== "undefined") {
                    window.location.assign(error.response.data.redirect);
                }
            },
        });
    } else if (eobs == "403") {
        Swal.fire({
            title: "ERROR!!! Not Authorized",
            text: "Contact admin for solution",
            timer: 300000,
            icon: "error",
            timerProgressBar: true,
            showConfirmButton: true,
            willClose: () => {
                if (typeof error.response.data.redirect !== "undefined") {
                    window.location.assign(error.response.data.redirect);
                }
            },
        });
    } else {
        Swal.fire({
            title: "Uncaught Errors!!!",
            text: error,
            timer: 300000,
            icon: "error",
            timerProgressBar: true,
            showConfirmButton: true,
            willClose: () => {
                if (typeof error.response.data.redirect !== "undefined") {
                    window.location.assign(error.response.data.redirect);
                }
            },
        });
    }
}
