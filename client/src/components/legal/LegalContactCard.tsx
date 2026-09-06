import React from 'react';

import { LEGAL } from '@/constants';

const LegalContactCard = () => {
    return (
        <div className="rounded-md bg-white p-4 shadow-card">
            <dl className="grid grid-cols-[120px_1fr] items-start gap-x-3 gap-y-1">
                <dt className="font-bold">屋号</dt>
                <dd>{LEGAL.TRADE_NAME}</dd>
                <dt className="font-bold">住所</dt>
                <dd className="whitespace-pre-wrap">{LEGAL.ADDRESS}</dd>
                <dt className="font-bold">メールアドレス</dt>
                <dd>
                    <a
                        href={`mailto:${LEGAL.SUPPORT_EMAIL}`}
                        className="text-primary-main underline">
                        {LEGAL.SUPPORT_EMAIL}
                    </a>
                </dd>
            </dl>
        </div>
    );
};

export default LegalContactCard;
