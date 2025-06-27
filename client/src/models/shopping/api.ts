// サーバーコンポーネントでのAPIリクエスト
// Next.jsではfetchを使用するのが推奨

// async function getShoppingCategories() {
//     try {
//         const res = await fetch('/shopping/categories');

//         if (!res.ok) {
//             throw new Error('Failed to fetch shopping categories');
//         }

//         return res.json();
//     } catch (error) {
//         console.error(error);
//         return [];
//     }
// }

// async function getShoppingItems() {
//     try {
//         const res = await fetch('/shopping/items');

//         if (!res.ok) {
//             throw new Error('Failed to fetch shopping items');
//         }

//         return res.json();
//     } catch (error) {
//         console.error(error);
//         return [];
//     }
// }
