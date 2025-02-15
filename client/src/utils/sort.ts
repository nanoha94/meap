interface Orderable {
    order: number;
    isDefault?: boolean;
}

export const sort = <T extends Orderable>(items: T[]) => {
    return [...items].sort((a, b) => {
        if (a.isDefault && !b.isDefault) return 1;
        if (!a.isDefault && b.isDefault) return -1;
        return (a.order ?? 0) - (b.order ?? 0);
    });
};
