'use client';

import React from 'react';
import Script from 'next/script';

import { PAYJP_SCRIPT_URL } from '@/constants/payjp';
import { notifyPayjpScriptLoaded } from '@/lib/payjpClient';

const PayjpScript = () => (
    <Script
        src={PAYJP_SCRIPT_URL}
        strategy="afterInteractive"
        onLoad={notifyPayjpScriptLoaded}
    />
);

export default PayjpScript;
