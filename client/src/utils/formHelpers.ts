type HasEmptyItemParams<T extends { id?: string; name: string }> = {
    items: T[];
};

export function hasEmptyItem<T extends { id?: string; name: string }>({
    items,
}: HasEmptyItemParams<T>) {
    const emptyItem = items.filter(item => item.name === '');

    if (emptyItem.length > 0) {
        return items.findIndex(item => item.id === emptyItem[0].id);
    }
    return false;
}
