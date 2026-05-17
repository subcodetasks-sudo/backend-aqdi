<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V2\StoreEmployeeRequest;
use App\Http\Requests\Admin\V2\StoreEmployeeNoteRequest;
use App\Http\Requests\Admin\V2\StoreEmployeeSalaryRequest;
use App\Http\Requests\Admin\V2\UpdateEmployeeRequest;
use App\Http\Resources\Admin\V2\Api\EmployeeNoteResource;
use App\Http\Resources\Admin\V2\Api\EmployeeNotesListResource;
use App\Http\Resources\Admin\V2\Api\EmployeeResource;
use App\Http\Resources\Admin\V2\Api\EmployeeSalaryResource;
use App\Http\Resources\Admin\V2\Api\SalaryResource;
use App\Http\Traits\Responser;
use App\Models\Employee;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EmployeeController extends Controller
{
    use Responser;

    protected function employeeBaseRelations(): array
    {
        return [
            'roleRelation',
            'salaries' => fn ($q) => $q->orderByDesc('created_at'),
            'notes' => fn ($q) => $q->orderByDesc('addition_date')->orderByDesc('created_at'),
            'receivedContract' => fn ($q) => $q->with('contract')->orderByDesc('created_at'),
            'refundableContract' => fn ($q) => $q->with('contract')->orderByDesc('created_at'),
        ];
    }

    protected function loadEmployeeWithFullDetails(Employee $employee): Employee
    {
        $employee->load($this->employeeBaseRelations());
        $employee->loadCount(['salaries', 'notes', 'receivedContract', 'refundableContract']);

        return $employee;
    }

  public function login_check(Request $request)
{
    try {

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $employee = Employee::with('roleRelation')
            ->where('email', $validated['email'])
            ->first();

        if (!$employee || !Hash::check($validated['password'], $employee->password)) {
            return response()->json([
                'message' => trans('api.credentials_error'),
                'success' => false,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$employee->is_active) {
            return response()->json([
                'message' => trans('api.employee_inactive'),
                'success' => false,
            ], Response::HTTP_FORBIDDEN);
        }

        if ($employee->blocked_until && now()->lessThan($employee->blocked_until)) {
            return response()->json([
                'message' => trans('api.employee_account_blocked'),
                'success' => false,
            ], Response::HTTP_FORBIDDEN);
        }

        $employee->tokens()->delete();

        $token = $employee->createToken('admin-employee')->plainTextToken;

        return response()->json([
            'message' => trans('api.login_success'),
            'success' => true,
            'data' => [

                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'base_salary' => $employee->base_salary,
                'role' => $employee->role,
                'role_id' => $employee->role_id,

                'is_active' => (bool) $employee->is_active,
                'is_online' => (bool) $employee->is_online,

                'is_blocked' => $employee->blocked_until
                    ? now()->lessThan($employee->blocked_until)
                    : false,

                'blocked_until' => $employee->blocked_until?->format('Y-m-d H:i:s'),
                'reason_of_block' => $employee->reason_of_block,

                'profile_image' => $employee->profile_image
                    ? url($employee->profile_image)
                    : null,

                'facebook' => $employee->facebook,
                'instagram' => $employee->instagram,
                'whatsapp' => $employee->whatsapp,
                'snapchat' => $employee->snapchat,
                'tiktok' => $employee->tiktok,
                'twitter' => $employee->twitter,

                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], Response::HTTP_OK);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => __('The given data was invalid.'),
            'success' => false,
            'errors' => $e->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

    } catch (Throwable $e) {

        return response()->json([
            'message' => trans('api.error_occurred') . ': ' . $e->getMessage(),
            'success' => false,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            $this->loadEmployeeWithFullDetails($employee);

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
            $employee = Employee::findOrFail($id);
            $this->loadEmployeeWithFullDetails($employee);

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
            $this->loadEmployeeWithFullDetails($employee);

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
    public function storeNote(StoreEmployeeNoteRequest $request, int $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $note = $employee->notes()->create([
                'addition_date' => $request->date('addition_date'),
                'notes_by_manger' => $request->input('note'),
            ]);

            return $this->apiResponse(
                new EmployeeNoteResource($note),
                trans('api.employee_note_created_successfully'),
                201
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function employeeNotes(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => ['sometimes', 'integer', 'exists:employees,id'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $query = Employee::query()
                ->select(['id', 'name', 'email', 'phone'])
                ->with(['notes' => fn ($q) => $q->orderByDesc('addition_date')->orderByDesc('created_at')]);

            if (! empty($validated['employee_id'])) {
                $query->where('id', $validated['employee_id']);
            }

            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $employees = $query->latest()->paginate($perPage);

            return $this->apiResponse(
                [
                    'items' => EmployeeNotesListResource::collection($employees),
                    'pagination' => $this->paginate($employees),
                ],
                trans('api.success')
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function storeSalary(StoreEmployeeSalaryRequest $request, int $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $data = $request->validated();
            $data['month'] = $request->date('due_date')->format('Y-m');

            $salary = $employee->salaries()->create($data);

            return $this->apiResponse(
                new SalaryResource($salary),
                trans('api.employee_salary_created_successfully'),
                201
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function employeeSalary(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => ['sometimes', 'integer', 'exists:employees,id'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $query = Employee::query()
                ->select(['id', 'name', 'email', 'phone', 'base_salary'])
                ->with(['salaries' => fn ($q) => $q->orderByDesc('created_at')]);

            if (! empty($validated['employee_id'])) {
                $query->where('id', $validated['employee_id']);
            }

            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $query->latest();
            $totalBaseSalary = (clone $query)->sum('base_salary');
            $employees = $query->paginate($perPage);

            return $this->apiResponse(
                [
                    'items' => EmployeeSalaryResource::collection($employees),
                    'total_base_salary' => $totalBaseSalary,
                    'pagination' => $this->paginate($employees),
                ],
                trans('api.success')
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

}
