/**
 * data-item-id属性を持つ要素にフォーカスを当てる
 * @param itemId アイテムID
 * @returns フォーカスが当たった場合はtrue、要素が見つからなかった場合はfalse
 */
export const focusItemById = (itemId: string): boolean => {
    const inputElement = document.querySelector(
        `[data-item-id="${itemId}"]`,
    ) as HTMLInputElement;
    if (inputElement) {
        inputElement.focus();
        return true;
    }
    return false;
};
