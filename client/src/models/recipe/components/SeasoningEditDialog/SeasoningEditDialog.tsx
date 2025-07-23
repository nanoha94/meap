'use client';
import { Dialog } from '@/components/common';
import {
    RECIPE_SETTING_DIALOG_CONFIGS,
    RECIPE_SETTING_DIALOG_NAME,
} from '../../constants';
import React from 'react';
import EditForm from './EditForm';
import { useRecipeStore } from '../../hooks/recipeStores';
import { ISeasoning } from '@/types/api/recipe';

const SeasoningEditDialog = () => {
    const { dialogs, closeDialog } = useRecipeStore();
    const {
        isOpen,
        payload: { item: editingItem, editMode, onAction },
    } = dialogs.seasoningSetting;

    const handleSetValue = (value: ISeasoning) => {
        onAction(value);
        closeDialog('seasoningSetting');
    };

    return (
        <Dialog
            title={
                RECIPE_SETTING_DIALOG_CONFIGS[
                    RECIPE_SETTING_DIALOG_NAME.SEASONING
                ][editMode].title
            }
            isOpen={isOpen}
            onClose={() => closeDialog('seasoningSetting')}>
            <EditForm
                editingItem={editingItem}
                actionButtonText={
                    RECIPE_SETTING_DIALOG_CONFIGS[
                        RECIPE_SETTING_DIALOG_NAME.SEASONING
                    ][editMode].actionButtonText
                }
                onClose={() => closeDialog('seasoningSetting')}
                onAction={handleSetValue}
            />
        </Dialog>
    );
};

export default SeasoningEditDialog;
