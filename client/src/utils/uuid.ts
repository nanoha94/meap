// client/src/app/utils/uuid.ts
import { v4 as uuidv4 } from 'uuid';

export const generateUuid = () => {
    const uuid = uuidv4();
    return uuid;
};
