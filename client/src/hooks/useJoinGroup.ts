import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';

export const useJoinGroup = () => {
    const { addSnackbar } = useSnackbars();

    const joinGroup = async (
        token: string,
        isDelete: boolean,
    ): Promise<string | null> => {
        try {
            const res = await axios.post(`/api/group/users/join/${token}`, {
                isDelete,
            });

            if (res.data) {
                addSnackbar('success', res.data.message);
            }
        } catch (error) {
            if (error.response.status === 409) {
                return error.response.data.message;
            }
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        }
    };

    return {
        joinGroup,
    };
};
