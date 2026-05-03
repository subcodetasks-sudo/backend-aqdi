<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\ReaEstatType;
use Illuminate\Http\Request;

class TypeRealController extends Controller
{
    use Responser;

    /**
     * List all types
     */
    public function index()
    {
        $types = ReaEstatType::latest()->paginate(20);

        return $this->apiResponse(
            $types,
            trans('api.success')
        );
    }

    /**
     * Create new type
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_type' => 'required|in:housing,commercial',
            'name_ar'       => 'required|string|max:255',
        ]);

        $type = ReaEstatType::create([
            'contract_type' => $request->contract_type,
            'name_ar'       => $request->name_ar,
        ]);

        return $this->apiResponse(
            $type,
            trans('api.created_successfully')
        );
    }

    /**
     * Update type
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            
            'contract_type' => 'required|in:housing,commercial',
            'name_ar'       => 'required|string|max:255',
        ]);

        $type = ReaEstatType::findOrFail($id);

        if (! $type) {
            return $this->apiResponse(
                null,
                trans('api.not_found'),
                404
            );
        }

        $type->update([
            'contract_type' => $request->contract_type,
            'name_ar'       => $request->name_ar,
        ]);

        return $this->apiResponse(
            $type,
            trans('api.updated_successfully')
        );
    }

    /**
     * Delete type
     */
    public function destroy($id)
    {
        $type = ReaEstatType::find($id);

        if (! $type) {
            return $this->apiResponse(
                null,
                trans('api.not_found'),
                404
            );
        }

        $type->delete();

        return $this->apiResponse(
            [],
            trans('api.deleted_successfully')
        );
    }
}
