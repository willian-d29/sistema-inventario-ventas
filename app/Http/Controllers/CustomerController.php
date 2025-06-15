<?php

namespace App\Http\Controllers;

use App\Enums\Core\FilterFieldTypeEnum;
use App\Enums\Core\SortOrderEnum;
use App\Enums\Customer\CustomerFiltersEnum;
use App\Enums\Customer\CustomerSortFieldsEnum;
use App\Http\Requests\Customer\CustomerCreateRequest;
use App\Http\Requests\Customer\CustomerIndexRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(CustomerIndexRequest $request): Response
    {
        $search = $request->input('search');

        $clientes = User::where('role', 'cliente')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render(
            component: 'Customer/Index',
            props: [
                'customers' => $clientes,
                'filters'   => [
                    CustomerFiltersEnum::NAME->value       => [
                        'label'       => CustomerFiltersEnum::NAME->label(),
                        'placeholder' => 'Enter name.',
                        'type'        => FilterFieldTypeEnum::STRING->value,
                        'value'       => $request->validated()[CustomerFiltersEnum::NAME->value] ?? "",
                    ],
                    CustomerFiltersEnum::EMAIL->value      => [
                        'label'       => CustomerFiltersEnum::EMAIL->label(),
                        'placeholder' => 'Enter email.',
                        'type'        => FilterFieldTypeEnum::STRING->value,
                        'value'       => $request->validated()[CustomerFiltersEnum::EMAIL->value] ?? "",
                    ],
                    CustomerFiltersEnum::PHONE->value      => [
                        'label'       => CustomerFiltersEnum::PHONE->label(),
                        'placeholder' => 'Enter phone.',
                        'type'        => FilterFieldTypeEnum::STRING->value,
                        'value'       => $request->validated()[CustomerFiltersEnum::PHONE->value] ?? "",
                    ],
                    "sort_by"                              => [
                        'label'       => 'Sort By',
                        'placeholder' => 'Select a sort field',
                        'type'        => FilterFieldTypeEnum::SELECT_STATIC->value,
                        'value'       => $request->validated()['sort_by'] ?? "",
                        'options'     => \App\Helpers\BaseHelper::convertKeyValueToLabelValueArray(CustomerSortFieldsEnum::choices()),
                    ],
                    "sort_order"                           => [
                        'label'       => 'Sort order',
                        'placeholder' => 'Select a sort order',
                        'type'        => FilterFieldTypeEnum::SELECT_STATIC->value,
                        'value'       => $request->validated()['sort_order'] ?? "",
                        'options'     => \App\Helpers\BaseHelper::convertKeyValueToLabelValueArray(SortOrderEnum::choices()),
                    ],
                    CustomerFiltersEnum::CREATED_AT->value => [
                        'label'       => CustomerFiltersEnum::CREATED_AT->label(),
                        'placeholder' => 'Enter created at.',
                        'type'        => FilterFieldTypeEnum::DATETIME_RANGE->value,
                        'value'       => $request->validated()[CustomerFiltersEnum::CREATED_AT->value] ?? "",
                    ],
                ],
            ]
        );
    }

    public function store(CustomerCreateRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $password = isset($data['password']) && $data['password']
                ? Hash::make($data['password'])
                : null;

            User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'phone'    => $data['phone'] ?? null,
                'address'  => $data['address'] ?? null,
                'photo'    => $data['photo'] ?? null,
                'role'     => 'cliente',
                'password' => $password,
            ]);

            $flash = ["message" => 'Cliente creado correctamente.'];
        } catch (Exception $e) {
            $flash = [
                "isSuccess" => false,
                "message"   => "¡Error al crear el cliente!",
            ];

            Log::error("Customer creation failed!", [
                "message" => $e->getMessage(),
                "traces"  => $e->getTrace()
            ]);
        }

        return redirect()->route('customers.index')->with('flash', $flash);
    }

    public function update(CustomerUpdateRequest $request, $id): RedirectResponse
    {
        try {
            $data = $request->validated();

            $cliente = User::where('id', $id)->where('role', 'cliente')->firstOrFail();

            $cliente->update($data);

            $flash = ["message" => 'Cliente actualizado correctamente.'];
        } catch (Exception $e) {
            $flash = [
                "isSuccess" => false,
                "message"   => "¡Error al actualizar el cliente!",
            ];

            Log::error("Customer update failed!", [
                "message" => $e->getMessage(),
                "traces"  => $e->getTrace()
            ]);
        }

        return redirect()->route('customers.index')->with('flash', $flash);
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $cliente = User::where('id', $id)->where('role', 'cliente')->firstOrFail();
            $cliente->delete();

            $flash = ["message" => 'Cliente eliminado correctamente.'];
        } catch (Exception $e) {
            $flash = [
                "isSuccess" => false,
                "message"   => "¡Error al eliminar el cliente!",
            ];

            Log::error("Customer deletion failed!", [
                "message" => $e->getMessage(),
                "traces"  => $e->getTrace()
            ]);
        }

        return redirect()->route('customers.index')->with('flash', $flash);
    }
}
