'use client';

import React from 'react';
import Link from 'next/link';

import { LINK_TO } from '@/constants';
import { CircleHelp } from 'lucide-react';

const BASE_CLASS_NAME = 'text-primary-main font-bold underline transition-opacity hover:opacity-80';

const PlanChangeHelpLink = () => {
    return (
        <div className="flex items-center gap-x-2 text-base">
            <CircleHelp className="size-4 shrink-0 text-primary-main" />
            <p>プラン変更の仕組みについては、<Link href={LINK_TO.HELP.PLAN_CHANGE} className={BASE_CLASS_NAME}>こちら</Link>をご確認ください。</p>
        </div>
    );
};

export default PlanChangeHelpLink;
