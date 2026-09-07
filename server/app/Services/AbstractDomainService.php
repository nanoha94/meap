<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Group;
use App\Traits\TypeCheck;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class AbstractDomainService
{
    use TypeCheck;

    /**
     * true のサービスは create / bulkCreate / bulkUpdate / delete / bulkDelete 成功後にマスターキャッシュを破棄する
     */
    protected bool $forgetsMasterCacheOnWrite = false;

    abstract protected function getResourceName(): string;
    abstract protected function getGroupRelation(Group $group): HasMany | BelongsToMany;

    /**
     * 作成フィールドを取得
     * @return array 作成フィールド
     */
    protected function getCreateFields(): array
    {
        return [];
    }

    /**
     * 更新フィールドを取得
     * @return array 更新フィールド
     */
    protected function getUpdateFields(): array
    {
        return [];
    }

    /**
     * 選択カラムを取得
     * @return array 選択カラム
     */
    protected function getSelectColumns(): array
    {
        return [];
    }

    /**
     * 関連テーブルのカラムを取得
     * @return array 関連テーブルのカラム名
     */
    protected function getWithColumns(): array
    {
        return [];
    }

    /**
     * グループ化カラムを取得
     * @return array グループ化カラム
     */
    protected function getGroupBy(): string | null
    {
        return null;
    }

    /**
     * 並び順のカラムを取得
     * @return string | null 並び順のカラム
     */
    protected function getOrderBy(): string | null
    {
        return null;
    }

    /**
     * 削除前の検証処理
     *
     * @param Collection $items 削除対象のアイテムコレクション
     * @throws HttpException 削除できない条件に該当する場合
     */
    protected function validateBeforeDelete(Collection $items): void {}

    /**
     * 一覧レスポンスをフォーマット
     * @param Model|Collection $item Modelインスタンス、またはGROUP BY使用時のCollection
     * @return array
     */
    protected function formatIndexResponse(Model|Collection $item): array
    {
        return [];
    }

    /**
     * 詳細レスポンスをフォーマット
     * @param Model $item
     * @return array
     */
    protected function formatShowResponse(Model $item): array
    {
        return [];
    }

    public function index(Group $group): array
    {
        $relation = $this->getGroupRelation($group);
        $query = $relation;

        // BelongsToManyリレーションの場合はselect()を使わない
        // select()を使うとテーブル名のプレフィックスが必要になるため
        if (!($relation instanceof BelongsToMany) && $this->getSelectColumns()) {
            $query = $query->select($this->getSelectColumns());
        }

        if ($this->getWithColumns()) {
            $query->with($this->getWithColumns());
        }

        if ($this->getOrderBy()) {
            $query->orderBy($this->getOrderBy());
        }

        $items = $query->get();

        // getのあとにgroupByを適用する
        if ($this->getGroupBy()) {
            $items = $items->groupBy($this->getGroupBy())->values();
        }

        return $items->map(function ($item) {
            return $this->formatIndexResponse($item);
        })->toArray();
    }

    /**
     * アイテムを作成
     *
     * @param array $data 作成データ
     * @param Group $group グループモデル
     */
    public function create(array $data, Group $group): ?string
    {
        DB::transaction(function () use ($data, $group) {
            $createData = [];
            foreach ($this->getCreateFields() as $field => $dataKey) {
                $createData[$field] = $data[$dataKey];
            }

            $this->getGroupRelation($group)->create($createData);
        });
        $this->forgetMasterGroupCacheIfNeeded($group);

        return null;
    }

    /**
     * アイテムを一括作成
     *
     * @param array $data 作成データの配列
     * @param Group $group グループモデル
     * @return array 作成されたアイテムの配列
     */
    public function bulkCreate(array $data, Group $group): array
    {
        $result = DB::transaction(function () use ($data, $group) {
            $result = [];
            foreach ($data as $item) {
                $createData = [];
                foreach ($this->getCreateFields() as $field => $dataKey) {
                    $createData[$field] = $item[$dataKey];
                }
                $result[] = $this->getGroupRelation($group)->create($createData);
            }

            return $result;
        });
        $this->forgetMasterGroupCacheIfNeeded($group);

        return $result;
    }

    /**
     * アイテムを取得
     *
     * @param string $id アイテムID
     * @param Group $group グループモデル
     * @return array アイテムのレスポンスデータ
     * @throws HttpException アイテムが見つからない場合
     */
    public function show(string $id, Group $group): array
    {
        return DB::transaction(function () use ($id, $group) {
            $relation = $this->getGroupRelation($group);
            $item = $relation->where('id', $id);

            // BelongsToManyリレーションの場合はselect()を使わない
            if (!($relation instanceof BelongsToMany) && $this->getSelectColumns()) {
                $item = $item->select($this->getSelectColumns());
            }

            if ($this->getWithColumns()) {
                $item->with($this->getWithColumns());
            }

            $result = $item->first();

            if (!$result) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => $this->getResourceName()])
                );
            }

            return $this->formatShowResponse($result);
        });
    }

    /**
     * 一括更新処理
     *
     * @param array $data 更新データの配列
     * @param Group $group グループモデル
     * @return Collection 更新されたレコードのコレクション
     */
    public function bulkUpdate(array $data, Group $group): array
    {
        $result = DB::transaction(function () use ($data, $group) {
            $requestedIds = array_column($data, 'id');
            $items = $this->findItemsByIds($requestedIds, $group);
            $result = [];

            foreach ($data as $item) {
                $updateData = [];
                foreach ($this->getUpdateFields() as $field => $dataKey) {
                    $updateData[$field] = $item[$dataKey];
                }
                $items[$item['id']]->update($updateData);

                $result[] = [];
            }

            return $result;
        });
        $this->forgetMasterGroupCacheIfNeeded($group);

        return $result;
    }

    /**
     * 削除処理
     *
     * @param string $id 削除対象のID
     * @param Group $group グループモデル
     * @return Model 削除されたアイテム
     * @throws HttpException アイテムが見つからない場合
     */
    public function delete(string $id, Group $group): Model
    {
        $deletedItem = DB::transaction(function () use ($id, $group) {
            $item = $this->findItemsByIds([$id], $group)->first();

            // 削除前の検証
            $this->validateBeforeDelete(new Collection([$item]));

            // 削除前にアイテムのコピーを保存
            $deletedItem = clone $item;

            // 削除処理
            $item->delete();

            // orderの再編成が必要な場合
            if ($this->getOrderBy()) {
                $this->reorderItems($group);
            }

            return $deletedItem;
        });
        $this->forgetMasterGroupCacheIfNeeded($group);

        return $deletedItem;
    }

    /**
     * 一括削除処理
     *
     * @param array $ids 削除対象のID配列
     * @param Group $group グループモデル
     * @return int 削除されたレコード数
     */
    public function bulkDelete(array $ids, Group $group): int
    {
        $count = DB::transaction(function () use ($ids, $group) {
            $items = $this->findItemsByIds($ids, $group);

            // 削除前の検証
            $this->validateBeforeDelete($items);

            // 削除処理
            $items->each->delete();

            // orderの再編成が必要な場合
            if ($this->getOrderBy()) {
                $this->reorderItems($group);
            }

            return $items->count();
        });
        $this->forgetMasterGroupCacheIfNeeded($group);

        return $count;
    }

    /**
     * 指定されたIDのアイテムをグループ内で検索
     *
     * @param array $ids ID配列
     * @param Group $group グループ
     * @return Collection 見つかったアイテムのコレクション（idでキー化）
     * @throws HttpException アイテムが見つからない場合
     */
    public function findItemsByIds(array $ids, Group $group): Collection
    {
        $relation = $this->getGroupRelation($group);
        $items = $relation->whereIn('id', $ids);

        // BelongsToManyリレーションの場合はselect()を使わない
        // このメソッドではselect()を使っていないが、将来の拡張のためにコメントを追加

        if ($this->getWithColumns()) {
            $items->with($this->getWithColumns());
        }

        $items = $items->get()->keyBy('id');

        $notFoundIds = array_diff($ids, $items->keys()->toArray());
        if (!empty($notFoundIds)) {
            throw new HttpException(
                HttpStatusCode::NOT_FOUND->value,
                __('api.not_found', ['attribute' => $this->getResourceName()])
            );
        }

        return $items;
    }

    /**
     * orderの再編成処理
     *
     * @param Group $group グループモデル
     * @return void
     */
    private function reorderItems(Group $group): void
    {
        $remainingItems = $this->getGroupRelation($group)
            ->orderBy($this->getOrderBy())
            ->get();

        foreach ($remainingItems as $index => $item) {
            $item->update(['order' => $index]);
        }
    }

    private function forgetMasterGroupCacheIfNeeded(Group $group): void
    {
        if (!$this->forgetsMasterCacheOnWrite) {
            return;
        }
        MasterService::forgetGroupCache($group);
    }
}
