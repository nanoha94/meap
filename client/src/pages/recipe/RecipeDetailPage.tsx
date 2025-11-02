'use client';
import { LoadingAnimation } from '@/components/common';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { IRecipe } from '@/types/api/recipe';
import Image from 'next/image';

interface Props {
    recipe?: IRecipe;
}

const RecipeDetailPage = ({ recipe }: Props) => {
    const { isLoadings } = useRecipeStore();
    return (
        <>
            {isLoadings.recipe && <LoadingAnimation />}
            <div className="p-5 pb-[60px] grid grid-cols-[repeat(auto-fill,_minmax(350px,_1fr))] gap-x-10 gap-y-5 md:px-10">
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* サムネイル */}
                    <div className="relative w-full h-auto aspect-video bg-gray-light rounded-lg overflow-hidden transition-opcity">
                        {recipe?.thumbnail && (
                            <Image
                                src={recipe?.thumbnail.src}
                                alt="thumbnail"
                                width={recipe?.thumbnail.width}
                                height={recipe?.thumbnail.height}
                                className="absolute top-0 left-0 w-full h-full object-cover"
                            />
                        )}
                    </div>
                    {/* 料理名 */}
                    <div className="text-xl">{recipe?.name}</div>
                    {/* カテゴリー */}
                    {recipe?.categories && recipe?.categories.length > 0 && (
                        <ul className="flex flex-wrap gap-x-3">
                            {recipe?.categories.map(category => (
                                <li
                                    key={category.id}
                                    className="py-1 px-2 text-xs leading-none text-gray-main rounded-full border border-gray-main">
                                    {category.name}
                                </li>
                            ))}
                        </ul>
                    )}
                    {/* 食材 */}
                    {recipe?.ingredients && recipe?.ingredients.length > 0 && (
                        <div className="flex flex-col gap-y-1">
                            <div>食材</div>
                            <ul>
                                {recipe?.ingredients.map(v => (
                                    <li
                                        key={v.id}
                                        className="relative pl-3 before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:inline-block before:w-1 before:h-1 before:bg-black before:rounded-full">
                                        {v.name}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* レシピ */}
                    {/* {recipe?.url ||
                        (recipe?.instructions && (
                            <div className="flex flex-col gap-y-1">
                                <div>レシピ</div>
                                <a
                                    href={recipe?.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary-main underline transition-colors hover:text-accent-main">
                                    {recipe?.url}
                                </a>
                                <div>{recipe?.instructions}</div>
                            </div>
                        ))} */}

                    {/* メモ */}
                    {recipe?.memo && (
                        <div className="flex flex-col gap-y-1">
                            <div>メモ</div>
                            <div>{recipe?.memo}</div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
};

export default RecipeDetailPage;
