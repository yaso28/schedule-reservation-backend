<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

abstract class RepositoryBase
{
    protected function select($model, $id, $relationsToLoad = null)
    {
        $query = $model::query();
        if ($relationsToLoad) {
            $query = $query->with($relationsToLoad);
        }
        return $query->findOrFail($id);
    }

    protected function insert($model, $attributes)
    {
        return $model::create($attributes)->id;
    }

    protected function update($model, $id, $attributes)
    {
        $model::where('id', $id)->update($attributes);
        return $id;
    }

    public function saveEntity($entity)
    {
        if (!$entity->id || $entity->isDirty()) {
            $entity->save();
        }
        return $entity->id;
    }

    public function saveEntityList($entityList)
    {
        DB::transaction(function () use ($entityList) {
            foreach ($entityList as $entity) {
                $this->saveEntity($entity);
            }
        });
    }

    protected function selectAllMaster($model, $relationsToLoad = null)
    {
        $query = $model::query();
        if ($relationsToLoad) {
            $query = $query->with($relationsToLoad);
        }
        return $query->orderBy('order_reverse', 'desc')
            ->orderBy('id')
            ->get();
    }

    protected function reorderMaster($model, $dataList)
    {
        DB::transaction(function () use ($model, $dataList) {
            foreach ($dataList as $data) {
                $model::where('id', $data['id'])
                    ->update(['order_reverse' => $data['order_reverse']]);
            }
        });
    }

    protected function selectCategoryRecord($model, $categoryName, $keyName)
    {
        return $model::where('category_name', $categoryName)
            ->where('key_name', $keyName)
            ->firstOrFail();
    }

    protected function updateCategoryRecord($model, $categoryName, $keyName, $attributes)
    {
        $model::where('category_name', $categoryName)
            ->where('key_name', $keyName)
            ->update($attributes);
    }
}
