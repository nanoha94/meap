import React from 'react';
import { LucideProps } from 'lucide-react';

import { COLOR_VARIANT } from '@/constants';

interface ActionButtonBase {
    label: string;
    icon: React.ReactElement<LucideProps>;
    color?: (typeof COLOR_VARIANT)['ALERT'];
}

/** href 指定時は <Link> として描画。このとき onClick は指定しない */
export interface ActionButtonLink extends ActionButtonBase {
    href: string;
    onClick?: never;
}

/** 画面遷移以外のアクション用。onClick 必須 */
export interface ActionButtonButton extends ActionButtonBase {
    href?: never;
    onClick: () => void;
}

export type ActionButton = ActionButtonLink | ActionButtonButton;
