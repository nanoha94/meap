import Image from 'next/image';

const MaintenancePage = () => {
    return (
        <div className="relative flex min-h-dvh items-center justify-center bg-gray-background px-4 sm:px-6">
            <div className="flex w-full max-w-xl flex-col items-center text-center">
                <Image
                    src="/images/meap-logo.png"
                    alt="meap"
                    width={1224}
                    height={456}
                    loading="eager"
                    className="h-12 w-auto sm:h-14"
                />

                <h1 className="mt-8 text-xl font-bold text-black sm:text-2xl">
                    ただいまメンテナンス中です
                </h1>

                <p className="mt-4 text-base leading-relaxed text-gray-main">
                    ご不便をおかけします。作業が終わり次第、通常どおりご利用いただけます。<br />しばらくしてから再度お試しください。
                </p>
            </div>
        </div>
    );
};

export default MaintenancePage;
