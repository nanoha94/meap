import { cache } from 'react';

import { fetchData } from '@/lib/apiClient';
import { IGetUserResponse } from '@/types';

/** 同一 RSC リクエスト内で /user 取得を dedupe する */
export const fetchCurrentUser = cache(() =>
    fetchData<IGetUserResponse>('/user', { suppressUnauthorizedLog: true }),
);
