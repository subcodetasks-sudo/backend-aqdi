<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\AppStatusService;
use Illuminate\Http\Request;

class AppStatusController extends Controller
{
    use Responser;

    public function __construct(protected AppStatusService $appStatus)
    {
    }

    /**
     * GET /api/v2/app-status
     * GET /api/v2/app-status?platform=ios&current_version=1.0.3
     *
     * Call on splash. If website/mobile is_open is false, show maintenance.
     * If update.force_update is true, block and send the user to store_url.
     */
    public function show(Request $request)
    {
        $platform = $request->query('platform');
        $currentVersion = $request->query('current_version', $request->query('version'));

        return $this->apiResponse(
            $this->appStatus->publicPayload(
                is_string($platform) ? $platform : null,
                is_string($currentVersion) ? $currentVersion : null
            ),
            trans('api.success')
        );
    }
}
