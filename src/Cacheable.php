<?php

namespace Vanry\Cache;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait Cacheable
{
    public static function bootCacheable()
    {
        static::saved(function ($model) {
            $model->clearCache();
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }

    public function clearCache()
    {
        Cache::forget($this->cacheKey());
    }

    public function cacheKey($key = null)
    {
        $cacheKey = $this->getTable().':'.$this->getKey();

        return is_null($key) ? $cacheKey : "{$cacheKey}:{$key}";
    }

    public static function findByIds(Collection $ids)
    {
        return $ids->map(function ($id) {
            return static::findById($id);
        })->reject(function ($model) {
            return is_null($model);
        });
    }

    public static function findById($id)
    {
        $model = new static;

        $model->setAttribute($model->getKeyName(), $id);

        return Cache::sear($model->cacheKey(), function () use ($id) {
            return static::find($id);
        });
    }

    public static function findByIdOrFail($id)
    {
        $model = static::findById($id);

        if (is_null($model)) {
            throw (new ModelNotFoundException)->setModel(static::class);
        }

        return $model;
    }
}
