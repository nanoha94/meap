'use client';
import { LoadingAnimation } from '@/components/common';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { IIngredientCategory } from '@/types/api/ingredient';
import { IRecipe } from '@/types/api/recipe';
import Image from 'next/image';

interface Props {
    fetchRecipe?: IRecipe;
    fetchIngredientCategories?: IIngredientCategory[];
}

const RecipeDetailPage = ({
    fetchRecipe,
    fetchIngredientCategories,
}: Props) => {
    const { isLoadings } = useRecipeStore();

    return (
        <>
            {isLoadings.recipe && <LoadingAnimation />}
            <div className="p-5 pb-[60px] grid grid-cols-[repeat(auto-fill,_minmax(350px,_1fr))] gap-x-10 gap-y-5 md:px-10">
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* 料理名 */}
                    <div className="text-2xl font-bold">
                        {fetchRecipe?.name}
                    </div>
                    {/* カテゴリー */}
                    {fetchRecipe?.categories &&
                        fetchRecipe?.categories.length > 0 && (
                            <ul className="flex flex-wrap gap-x-3">
                                {fetchRecipe?.categories.map(category => (
                                    <li
                                        key={category.id}
                                        className="py-1 px-2 text-xs leading-none text-gray-main rounded-full border border-gray-main">
                                        {category.name}
                                    </li>
                                ))}
                            </ul>
                        )}
                    {/* サムネイル */}
                    <div className="relative w-full h-auto aspect-video bg-gray-light rounded-lg overflow-hidden transition-opcity">
                        {fetchRecipe?.thumbnail && (
                            <Image
                                src={fetchRecipe?.thumbnail.src}
                                alt="thumbnail"
                                width={fetchRecipe?.thumbnail.width}
                                height={fetchRecipe?.thumbnail.height}
                                className="absolute top-0 left-0 w-full h-full object-cover"
                            />
                        )}
                    </div>
                    {/* 食材 */}
                    {fetchRecipe?.ingredients &&
                        fetchRecipe?.ingredients.length > 0 && (
                            <div>
                                <div className="mb-2 text-xl font-bold">
                                    材料【○○人分】
                                </div>
                                <div className="flex flex-col gap-y-5">
                                    {fetchIngredientCategories?.map(
                                        category =>
                                            fetchRecipe.ingredients.some(
                                                ingredient =>
                                                    ingredient.categoryId ===
                                                    category.id,
                                            ) && (
                                                <div key={category.id}>
                                                    <div className="mb-2 text-lg">
                                                        {category.name}
                                                    </div>
                                                    <ul className="flex flex-col gap-y-1">
                                                        {fetchRecipe.ingredients
                                                            .filter(
                                                                ingredient =>
                                                                    ingredient.categoryId ===
                                                                    category.id,
                                                            )
                                                            .map(ingredient => (
                                                                <li
                                                                    key={
                                                                        ingredient.id
                                                                    }
                                                                    className="relative pl-5 pr-2 py-1 flex justify-between border-b border-gray-border before:content-[''] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:inline-block before:w-1 before:h-1 before:bg-black before:rounded-full">
                                                                    <div>
                                                                        {
                                                                            ingredient.name
                                                                        }
                                                                    </div>
                                                                    <div>
                                                                        {ingredient
                                                                            .unit
                                                                            ?.position ===
                                                                            'prefix' &&
                                                                            ` ${ingredient.unit.name}`}
                                                                        {
                                                                            ingredient.quantity
                                                                        }
                                                                        {ingredient
                                                                            .unit
                                                                            ?.position ===
                                                                            'suffix' &&
                                                                            ` ${ingredient.unit.name}`}
                                                                    </div>
                                                                </li>
                                                            ))}
                                                    </ul>
                                                </div>
                                            ),
                                    )}
                                </div>
                            </div>
                        )}
                </div>
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* レシピ */}
                    {fetchRecipe?.url && (
                        <div className="flex flex-col gap-y-1">
                            <div className="text-xl font-bold">レシピ</div>
                            <a
                                href={fetchRecipe?.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-primary-main underline transition-colors hover:text-accent-main">
                                {fetchRecipe?.url}
                            </a>
                        </div>
                    )}
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
                    {fetchRecipe?.memo && (
                        <div className="flex flex-col gap-y-1">
                            <div className="text-xl font-bold">メモ</div>
                            <div>{fetchRecipe?.memo}</div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
};

export default RecipeDetailPage;
