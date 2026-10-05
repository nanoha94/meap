'use client';

import React from 'react';
import Script from 'next/script';

import { SQUARE_SCRIPT_URL } from '@/constants/square';
import { notifySquareScriptLoaded } from '@/lib/squareClient';

const SquareScript = () => (
    <Script
        src={SQUARE_SCRIPT_URL}
        strategy="afterInteractive"
        onLoad={notifySquareScriptLoaded}
    />
);

export default SquareScript;
