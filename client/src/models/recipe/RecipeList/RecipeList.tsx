import Image from 'next/image';

const RecipeList = () => {
    return (
        <div className="grid grid-cols-[repeat(auto-fill,_minmax(320px,_1fr))] gap-4">
            <div
                className="relative py-3 px-1 w-full text-left flex  bg-white"
                style={{ boxShadow: '1px 1px 5px rgba(0, 0, 0, 15%)' }}>
                <Image src="" alt="alt" width={100} height={100} />
            </div>
        </div>
    );
};

export default RecipeList;
