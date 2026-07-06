<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLawRequest;
use App\Models\Law;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class AdminLawController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $laws = Law::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Law $law): array => [
                'id' => $law->id,
                'key' => $law->key,
                'name' => $law->name,
                'description' => $law->description,
                'bonus' => $law->bonus ?? [],
            ])
            ->all();

        return $this->success($laws);
    }

    public function store(StoreLawRequest $request): JsonResponse
    {
        $name = (string) $request->string('name');

        $base = Str::slug($name, '_');
        $key = $base;
        $suffix = 2;

        while (Law::query()->where('key', $key)->exists()) {
            $key = $base.'_'.$suffix++;
        }

        $law = Law::create([
            'key' => $key,
            'name' => $name,
            'description' => $request->input('description'),
            'bonus' => [(string) $request->string('bonus_key') => $request->integer('bonus_value')],
            'is_active' => true,
        ]);

        return $this->success([
            'id' => $law->id,
            'key' => $law->key,
            'name' => $law->name,
            'description' => $law->description,
            'bonus' => $law->bonus,
        ], __('Law created.'), 201);
    }
}
