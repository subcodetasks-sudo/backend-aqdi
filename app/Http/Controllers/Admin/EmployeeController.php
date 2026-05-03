<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V2\StoreEmployeeRequest;
use App\Http\Requests\Admin\V2\UpdateEmployeeRequest;
use App\Http\Resources\Admin\V2\Api\EmployeeResource;
use App\Http\Traits\Responser;
use App\Models\Employee;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeController extends Controller
{
    use Responser;

    protected function employeeBaseRelations(): array
    {
        return ['roleRelation', 'salaries', 'notes', 'receivedContract', 'refundableContract'];
    }

    public function login_check(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $employee = Employee::where('email', $request->input('email'))->first();

            if (! $employee || ! Hash::check($request->input('password'), $employee->password)) {
                return $this->errorMessage(trans('api.credentials_error'), 401);
            }

            if (! $employee->is_active) {
                return $this->errorMessage(trans('api.employee_inactive'), 403);
            }

            if ($employee->blocked_until && now()->lessThan($employee->blocked_until)) {
                return $this->errorMessage(trans('api.employee_account_blocked'), 403);
            }

            $employee->tokens()->delete();
            $token = $employee->createToken('admin-employee')->plainTextToken;

            return $this->apiResponse([
                'employee' => new EmployeeResource($employee->load($this->employeeBaseRelations())),
                'token' => $token,
                'token_type' => 'Bearer',
            ], trans('api.login_success'));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()?->currentAccessToken()?->delete();

            return $this->successMessage(trans('api.logout_success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = Employee::query()
                ->with(['roleRelation'])
                ->withCount(['salaries', 'notes', 'receivedContract', 'refundableContract']);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            }

            $sortBy = $request->input('sort_by', 'created_at');
            $allowedSort = ['created_at', 'name', 'email', 'id'];
            if (! in_array($sortBy, $allowedSort, true)) {
                $sortBy = 'created_at';
            }
            $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

            $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
            $employees = $query->orderBy($sortBy, $sortOrder)->paginate($perPage);

            return $this->apiResponse([
                'items' => EmployeeResource::collection($employees),
                'pagination' => $this->paginate($employees),
            ], trans('api.success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function store(StoreEmployeeRequest $request)
    {
        try {
            $data = $request->validated();

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('employees/profiles', $imageName, 'public');
                $data['profile_image'] = 'storage/' . $imagePath;
            }

            $employee = Employee::create($data);
            $employee->load($this->employeeBaseRelations());

            return $this->apiResponse(
                new EmployeeResource($employee),
                trans('api.employee_created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $employee = Employee::with([
                'roleRelation',
                'salaries',
                'notes',
                'receivedContract',
                'refundableContract',
            ])->findOrFail($id);

            return $this->apiResponse(
                new EmployeeResource($employee),
                trans('api.success')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateEmployeeRequest $request, int $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $data = $request->validated();

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            if ($request->hasFile('profile_image')) {
                if ($employee->profile_image && Storage::disk('public')->exists(str_replace('storage/', '', $employee->profile_image))) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $employee->profile_image));
                }

                $image = $request->file('profile_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('employees/profiles', $imageName, 'public');
                $data['profile_image'] = 'storage/' . $imagePath;
            }

            $employee->update($data);
            $employee->load($this->employeeBaseRelations());

            return $this->apiResponse(
                new EmployeeResource($employee),
                trans('api.employee_updated_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            if ($employee->profile_image && Storage::disk('public')->exists(str_replace('storage/', '', $employee->profile_image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $employee->profile_image));
            }

            $employee->tokens()->delete();
            $employee->delete();

            return $this->apiResponse([], trans('api.employee_deleted_successfully'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function toggleStatus(int $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->update(['is_active' => ! $employee->is_active]);
            $employee->load($this->employeeBaseRelations());

            return $this->apiResponse(
                new EmployeeResource($employee),
                trans('api.employee_status_updated')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function block(Request $request, int $id)
    {
        try {
            $request->validate([
                'blocked_until' => ['required', 'date', 'after:now'],
                'reason_of_block' => ['required', 'string'],
            ]);

            $employee = Employee::findOrFail($id);
            $employee->update([
                'blocked_until' => $request->input('blocked_until'),
                'reason_of_block' => $request->input('reason_of_block'),
                'is_active' => false,
            ]);
            $employee->load($this->employeeBaseRelations());

            return $this->apiResponse(
                new EmployeeResource($employee),
                trans('api.employee_blocked_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function unblock(int $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->update([
                'blocked_until' => null,
                'reason_of_block' => null,
                'is_active' => true,
            ]);
            $employee->load($this->employeeBaseRelations());

            return $this->apiResponse(
                new EmployeeResource($employee),
                trans('api.employee_unblocked_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }
}
