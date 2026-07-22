<?php

namespace App\Http\Controllers;

use App\Enums\Core\FilterFieldTypeEnum;
use App\Enums\Core\SortOrderEnum;
use App\Enums\Employee\EmployeeFiltersEnum;
use App\Enums\Employee\EmployeeSortFieldsEnum;
use App\Exceptions\EmployeeNotFoundException;
use App\Helpers\BaseHelper;
use App\Http\Requests\Employee\EmployeeCreateRequest;
use App\Http\Requests\Employee\EmployeeIndexRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest;
use App\Models\User;
use App\Services\EmployeeService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $service)
    {
    }

    public function index(EmployeeIndexRequest $request)
    {
        if ($request->inertia == 'disabled') {
            $query = $request->validated();
            $query['sort_by'] = EmployeeSortFieldsEnum::NAME->value;

            return $this->service->getAll($query);
        }

        return Inertia::render(
            component: 'Employee/Index',
            props: [
                'employees' => $this->service->getAll($request->validated()),
                'filters' => [
                    EmployeeFiltersEnum::NAME->value => [
                        'label' => EmployeeFiltersEnum::NAME->label(),
                        'placeholder' => 'Enter name.',
                        'type' => FilterFieldTypeEnum::STRING->value,
                        'value' => $request->validated()[EmployeeFiltersEnum::NAME->value] ?? '',
                    ],
                    EmployeeFiltersEnum::EMAIL->value => [
                        'label' => EmployeeFiltersEnum::EMAIL->label(),
                        'placeholder' => 'Enter email.',
                        'type' => FilterFieldTypeEnum::STRING->value,
                        'value' => $request->validated()[EmployeeFiltersEnum::EMAIL->value] ?? '',
                    ],
                    EmployeeFiltersEnum::PHONE->value => [
                        'label' => EmployeeFiltersEnum::PHONE->label(),
                        'placeholder' => 'Enter phone.',
                        'type' => FilterFieldTypeEnum::STRING->value,
                        'value' => $request->validated()[EmployeeFiltersEnum::PHONE->value] ?? '',
                    ],
                    EmployeeFiltersEnum::NID->value => [
                        'label' => EmployeeFiltersEnum::NID->label(),
                        'placeholder' => 'Enter NID.',
                        'type' => FilterFieldTypeEnum::STRING->value,
                        'value' => $request->validated()[EmployeeFiltersEnum::NID->value] ?? '',
                    ],
                    EmployeeFiltersEnum::SALARY->value => [
                        'label' => EmployeeFiltersEnum::SALARY->label(),
                        'placeholder' => 'Enter salary.',
                        'type' => FilterFieldTypeEnum::NUMBER_RANGE->value,
                        'value' => $request->validated()[EmployeeFiltersEnum::SALARY->value] ?? '',
                    ],
                    EmployeeFiltersEnum::JOINING_DATE->value => [
                        'label' => EmployeeFiltersEnum::JOINING_DATE->label(),
                        'placeholder' => 'Enter joining date.',
                        'type' => FilterFieldTypeEnum::DATE_RANGE->value,
                        'value' => $request->validated()[EmployeeFiltersEnum::JOINING_DATE->value] ?? '',
                    ],
                    'sort_by' => [
                        'label' => 'Sort By',
                        'placeholder' => 'Select a sort field',
                        'type' => FilterFieldTypeEnum::SELECT_STATIC->value,
                        'value' => $request->validated()['sort_by'] ?? '',
                        'options' => BaseHelper::convertKeyValueToLabelValueArray(EmployeeSortFieldsEnum::choices()),
                    ],
                    'sort_order' => [
                        'label' => 'Sort order',
                        'placeholder' => 'Select a sort order',
                        'type' => FilterFieldTypeEnum::SELECT_STATIC->value,
                        'value' => $request->validated()['sort_order'] ?? '',
                        'options' => BaseHelper::convertKeyValueToLabelValueArray(SortOrderEnum::choices()),
                    ],
                    EmployeeFiltersEnum::CREATED_AT->value => [
                        'label' => EmployeeFiltersEnum::CREATED_AT->label(),
                        'placeholder' => 'Enter created at.',
                        'type' => FilterFieldTypeEnum::DATETIME_RANGE->value,
                        'value' => $request->validated()[EmployeeFiltersEnum::CREATED_AT->value] ?? '',
                    ],
                ],
            ]);
    }

    public function store(EmployeeCreateRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => 'cajero',
                    'password' => Hash::make($data['password']),
                ]);

                $this->service->create($data + ['user_id' => $user->id]);
            });
            $flash = ['message' => __('messages.employees.created')];
        } catch (Exception $e) {
            Log::error('Employee creation failed!', ['exception' => $e]);
            $flash = ['isSuccess' => false, 'message' => __('messages.employees.create_failed')];
        }

        return redirect()->route('employees.index')->with('flash', $flash);
    }

    public function update(EmployeeUpdateRequest $request, $id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $id) {
                $employee = $this->service->findByIdOrFail($id);
                $data = $request->validated();
                $employee->user?->update(array_filter([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => ! empty($data['password']) ? Hash::make($data['password']) : null,
                ], fn ($value) => $value !== null));
                $this->service->update($id, $data);
            });
            $flash = [
                'message' => __('messages.employees.updated'),
            ];
        } catch (EmployeeNotFoundException $e) {
            $flash = [
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => __('messages.employees.update_failed'),
            ];

            Log::error('Employee update failed!', [
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('employees.index')
            ->with('flash', $flash);
    }

    public function destroy($id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($id) {
                $employee = $this->service->findByIdOrFail($id);
                $user = $employee->user;
                $this->service->delete($id);
                $user?->delete();
            });
            $flash = [
                'message' => __('messages.employees.deleted'),
            ];
        } catch (EmployeeNotFoundException $e) {
            $flash = [
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => __('messages.employees.delete_failed'),
            ];

            Log::error('Employee deletion failed!', [
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('employees.index')
            ->with('flash', $flash);
    }
}
