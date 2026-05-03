<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\OrderResource;
use App\Models\Contract;
use Illuminate\Http\Request;

class IncompleteContract extends Controller
{
     public function orders()
    {
        $orders = Contract::where('is_completed',0)->latest()->paginate(20);
        $orderCollection = OrderResource::collection($orders);
        return $this->apiResponse($orderCollection, trans('api.success'));
    }


    
}
