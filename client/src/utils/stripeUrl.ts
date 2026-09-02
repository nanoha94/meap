/**
 * Stripe 公式ドメイン（stripe.com およびそのサブドメイン）の HTTPS URL のみ許可する
 */
export function isAllowedStripeUrl(url: string): boolean {
    try {
        const parsed = new URL(url);

        if (parsed.protocol !== 'https:') {
            return false;
        }

        const hostname = parsed.hostname.toLowerCase();

        return hostname === 'stripe.com' || hostname.endsWith('.stripe.com');
    } catch {
        return false;
    }
}

/**
 * 許可された Stripe URL へ遷移する。不正 URL の場合は false を返す。
 */
export function openStripeUrl(
    url: string,
    target: '_self' | '_blank' = '_self',
): boolean {
    if (!isAllowedStripeUrl(url)) {
        return false;
    }

    if (target === '_blank') {
        window.open(url, '_blank', 'noopener,noreferrer');
    } else {
        window.location.href = url;
    }

    return true;
}
