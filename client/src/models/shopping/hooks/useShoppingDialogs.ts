import React from 'react';

export const useShoppingDialogs = () => {
    const [dialogs, setDialogs] = React.useState({
        categorySetting: false,
        itemSetting: false,
    });

    const openDialog = (dialogName: keyof typeof dialogs) => {
        setDialogs(prev => ({ ...prev, [dialogName]: true }));
    };

    const closeDialog = (dialogName: keyof typeof dialogs) => {
        setDialogs(prev => ({ ...prev, [dialogName]: false }));
    };

    return {
        dialogs,
        openDialog,
        closeDialog,
    };
};
