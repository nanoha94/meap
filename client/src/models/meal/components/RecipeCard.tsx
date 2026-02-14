
import { GripVertical, ImageIcon, Trash2 } from "lucide-react";
import Image from "next/image";

import { colors } from "@/constants";
import { IMealPlanItem } from "@/types";

type Props = {
    recipe: IMealPlanItem;
    isGrippable?: boolean; hasDeleteButton: false; onDelete?: () => void
} | {
    recipe: IMealPlanItem;
    isGrippable?: boolean;
    hasDeleteButton: true;
    onDelete: () => void;
};

const RecipeCard = ({ recipe, isGrippable = false, hasDeleteButton = false, onDelete }: Props) => {
    return (
        <div className="flex gap-x-2">
            {isGrippable && <GripVertical color={colors.gray.main} className="pt-1" />}
            <div className="relative flex-1">
                <div className="relative w-full h-auto aspect-[4/3] bg-gray-light rounded shadow-card md:rounded-lg overflow-hidden">
                    {recipe.thumbnail ? (
                        <Image
                            src={recipe.thumbnail.src}
                            alt={recipe.name}
                            width={recipe.thumbnail.width}
                            height={recipe.thumbnail.height}
                            className="absolute top-0 left-0 object-cover"
                        />
                    ) : (
                        <ImageIcon
                            size={40}
                            strokeWidth={1.5}
                            className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-main"
                        />
                    )}

                    <div className="absolute bottom-0 left-0 px-2 pt-2 pb-1 w-full leading-none bg-gradient-to-b from-white/0 via-white/80 to-white">
                        <span className="text-xs font-bold">{recipe.name}</span>
                    </div>
                </div>
                {hasDeleteButton && (
                    <button
                        type="button"
                        onClick={onDelete}
                        className="absolute -top-2.5 -right-2.5 p-1 
                    appearance-none rounded-full bg-alert-main transition-colors hover:bg-alert-light">
                        <Trash2 color={colors.white} size={20} />
                    </button>
                )}
            </div>
        </div>
    );
};

export default RecipeCard;
