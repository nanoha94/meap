'use client';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import Image from 'next/image';
import Link from 'next/link';

const RecipeList = () => {
    const { recipes } = useRecipeStore();

    return (
        <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] gap-3 ">
            {recipes.map(v => (
                <Link
                    href={`/recipe/${v.id}`}
                    key={v.id}
                    className="relative w-full text-left flex flex-col bg-white rounded transition-transform duration-500 cursor-pointer hover:-translate-y-2"
                    style={{ boxShadow: '1px 1px 5px rgba(0, 0, 0, 15%)' }}>
                    <div className="w-full h-auto aspect-video object-cover bg-gray-background">
                        {v.thumbnail && v.thumbnail.src && (
                            <Image
                                src={v.thumbnail.src}
                                alt={v.name}
                                width={v.thumbnail.width}
                                height={v.thumbnail.height}
                                className="w-full h-auto aspect-video object-cover rounded-t"
                            />
                        )}
                    </div>
                    <div className="p-2 flex flex-col gap-y-1">
                        <div>{v.name}</div>
                        <div className="text-xs text-black">
                            {v.categories
                                .map(category => category.name)
                                .join('/')}
                        </div>
                        <div className="text-xs text-black">前回作った日：</div>
                    </div>
                </Link>
            ))}
        </div>
    );
};

export default RecipeList;
