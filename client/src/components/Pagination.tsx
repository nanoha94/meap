import { colors } from "@/constants";
import { ChevronLeft, ChevronRight, Ellipsis } from "lucide-react";

type PageItem = number | "ellipsis";

interface Props {
    pageSize: number;
    currentPage: number;
    onPageChange: (page: number) => void;
}

/**
 * ページ番号の配列を生成する
 * @param pageSize ページ数
 * @param currentPage 現在のページ番号
 * @returns ページ番号の配列
 */
const getPageNumbers = (pageSize: number, currentPage: number): PageItem[] => {
    const visiblePages = new Set<number>();

    visiblePages.add(1);
    visiblePages.add(pageSize);

    for (let i = currentPage - 2; i <= currentPage + 2; i++) {
        if (i >= 1 && i <= pageSize) {
            visiblePages.add(i);
        }
    }

    const sorted = Array.from(visiblePages).sort((a, b) => a - b);
    const result: PageItem[] = [];

    for (let i = 0; i < sorted.length; i++) {
        if (i > 0 && sorted[i] - sorted[i - 1] > 1) {
            result.push("ellipsis");
        }
        result.push(sorted[i]);
    }

    return result;
};

const Pagination = ({ pageSize, currentPage, onPageChange }: Props) => {
    const pages = getPageNumbers(pageSize, currentPage);

    if (pageSize <= 1) return null;

    return (
        <ul className="flex items-center justify-center gap-x-4">
            <li>
                <button
                    onClick={() => onPageChange(currentPage - 1)} disabled={currentPage <= 1}
                    className="p-1 appearance-none rounded-full bg-white shadow-card transition-colors hover:bg-gray-light disabled:opacity-50 disabled:pointer-events-none">
                    <ChevronLeft color={colors.primary.main} size={24} />
                </button>
            </li>
            {pages.map((item) =>
                typeof item === "number" ? (
                    <li key={item}>
                        <button onClick={() => onPageChange(item)} disabled={currentPage === item} className={`w-8 h-8 p-1 flex items-center justify-center appearance-none rounded-full shadow-card transition-colors hover:bg-gray-light disabled:opacity-50 disabled:pointer-events-none ${currentPage === item ? 'bg-primary-main text-white' : 'bg-white text-primary-main'}`}>{item}</button>
                    </li>
                ) : (
                    <li key={item}>
                        <span className="w-8 h-8 flex items-center justify-center select-none"><Ellipsis color={colors.gray.main} size={24} /></span>
                    </li>
                )
            )}
            <li>
                <button onClick={() => onPageChange(currentPage + 1)} disabled={currentPage >= pageSize} className="p-1 appearance-none rounded-full bg-white shadow-card transition-colors hover:bg-gray-light disabled:opacity-50 disabled:pointer-events-none"><ChevronRight color={colors.primary.main} size={24} /></button>
            </li>
        </ul>
    );
};

export default Pagination;