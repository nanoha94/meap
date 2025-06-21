// client/src/app/utils/waitForLoading.ts

interface IWaitForLoadingProps {
    isLoading: boolean;
    interval?: number;
    timeout?: number;
}

export const waitForLoading = ({
    isLoading,
    interval = 100,
    timeout = 5000,
}: IWaitForLoadingProps) => {
    return new Promise((resolve, reject) => {
        const startTime = Date.now();

        const timer = setInterval(() => {
            if (!isLoading) {
                clearInterval(timer);
                resolve(true);
            } else if (Date.now() - startTime > timeout) {
                clearInterval(timer);
                reject(new Error('Timeout'));
            }
        }, interval);
    });
};
