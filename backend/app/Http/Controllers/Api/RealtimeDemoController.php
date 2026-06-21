<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\ServerAnnouncementBroadcasted;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RealtimeDemoController extends Controller
{
    use ApiResponse;

    public function broadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
        ]);

        broadcast(new ServerAnnouncementBroadcasted($validated['message']));

        return $this->success(null, __('Announcement broadcasted.'));
    }
}
