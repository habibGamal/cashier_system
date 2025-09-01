<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class BaseResource extends JsonResource
{
    /**
     * Convert all keys in the response array to camelCase, recursively.
     */
    protected function camelizeArray($array)
    {
        return collect($array)->mapWithKeys(function ($value, $key) {
            $newKey = Str::camel($key);

            if (is_array($value)) {
                $value = $this->camelizeArray($value);
            }

            return [$newKey => $value];
        })->toArray();
    }

    public function toArray($request)
    {
        return $this->camelizeArray(parent::toArray($request));
    }
}
