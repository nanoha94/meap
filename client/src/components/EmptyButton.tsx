'use client';

import { ButtonType } from "@/constants";
import { Plus } from "lucide-react";
import Link from "next/link";

const baseClassName = "w-full h-[60px] flex items-center justify-center bg-gray-background border-dashed border rounded transition-colors hover:bg-gray-light";
const iconClassName = "text-gray-main";

type EmptyButtonProps =
    | { href: string; className?: string } // hrefがある場合はLinkを返す
    | { onClick?: () => void; type?: ButtonType, className?: string } // hrefがない場合はbuttonを返す

const EmptyButton = ({ className, ...props }: EmptyButtonProps) => {
    const combinedClassName = [baseClassName, className].filter(Boolean).join(' ');

    // hrefがある場合はLinkを返す
    if ('href' in props && props.href) {
        return (
            <Link href={props.href} className={combinedClassName}>
                <Plus size={24} strokeWidth={2} className={iconClassName} />
            </Link>
        );
    }

    // hrefがない場合はbuttonを返す
    const buttonProps = props as { onClick?: () => void; type?: ButtonType };
    return (
        <button type={buttonProps.type ?? 'button'} onClick={buttonProps.onClick} className={combinedClassName}>
            <Plus size={24} strokeWidth={2} className={iconClassName} />
        </button>
    );
};

export default EmptyButton;