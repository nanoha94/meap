'use server';

import { cookies } from 'next/headers';

export async function clearRedirectCookie() {
    const cookieStore = await cookies();
    cookieStore.delete('redirectPath');
}
