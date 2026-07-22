<?php

namespace App\Http\Controllers;

use App\Enums\Cart\CartExpandEnum;
use App\Enums\Cart\CartFiltersEnum;
use App\Enums\Product\ProductExpandEnum;
use App\Enums\Product\ProductFiltersEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Exceptions\CartException;
use App\Exceptions\CartNotFoundException;
use App\Helpers\BaseHelper;
use App\Http\Requests\Cart\CartQuantityUpdateRequest;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Models\CashRegister;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CartService $cartService,
    ) {
    }

    public function index(ProductIndexRequest $request): Response
    {
        // Get products
        $productParams = $request->validated();
        $productParams[ProductFiltersEnum::STATUS->value] = ProductStatusEnum::ACTIVE->value;
        $productParams['per_page'] = 500;
        $productParams['expand'] = array_unique(array_merge($productParams['expand'] ?? [], [
            ProductExpandEnum::UNIT_TYPE->value,
        ]));

        // Get cart items
        $carts = $this->cartService->getAll([
            CartFiltersEnum::USER_ID->value => auth()->id(),
            'expand' => [
                CartExpandEnum::PRODUCT_UNIT_TYPE->value,
            ],
            'per_page' => 500,
        ]);

        // Calculate cart subtotal
        $cartSubtotal = 0;
        foreach ($carts as $cart) {
            $cartSubtotal += $cart->product->selling_price * $cart->quantity;
        }

        // Calculate total discount
        $discountData = BaseHelper::calculateDefaultDiscount(amount: $cartSubtotal);

        // Calculate total tax
        $taxData = BaseHelper::calculateTax(amount: $cartSubtotal);

        // Calculate total
        $total = BaseHelper::numberFormat(
            number: $cartSubtotal - $discountData['totalDiscount'] + $taxData['totalTax']
        );

        return Inertia::render(
            component: 'Cart/Pos',
            props: [
                'products' => $this->productService->getAll($productParams),
                'carts' => $carts,
                'cartSubtotal' => $cartSubtotal,
                'discountType' => $discountData['discountType'],
                'discount' => $discountData['discount'],
                'totalDiscount' => $discountData['totalDiscount'],
                'tax' => $taxData['tax'],
                'totalTax' => $taxData['totalTax'],
                'total' => $total,
                'paymentMethods' => PaymentMethodEnum::options(),
                'cashRegister' => CashRegister::query()
                    ->where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->latest('opened_at')
                    ->first(),
            ]
        );
    }

    public function addToCart(int $productId): RedirectResponse
    {
        try {
            $product = $this->productService->findByIdOrFail(id: $productId);

            $this->cartService->createOrUpdateForUser(
                product: $product,
                userId: auth()->id(),
            );

            $flash = [
                'message' => 'Producto agregado a la venta.',
            ];
        } catch (CartException $e) {
            $flash = [
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => 'No se pudo agregar el producto.',
            ];

            Log::error('Failed to add product to cart!', [
                'product_id' => $productId,
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('carts.index')
            ->with('flash', $flash);
    }

    public function scan(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:255']]);
        $product = Product::query()
            ->where('barcode', $validated['code'])
            ->orWhere('product_code', $validated['code'])
            ->orWhere('product_number', $validated['code'])
            ->first();

        if (! $product) {
            return redirect()->route('carts.index', ['keyword' => $validated['code']]);
        }

        return $this->addToCart($product->id);
    }

    public function updateQuantity(CartQuantityUpdateRequest $request, int $cartId): RedirectResponse
    {
        try {
            $this->cartService->updateQuantity(
                id: $cartId,
                userId: auth()->id(),
                payload: $request->validated(),
            );

            $flash = [
                'message' => 'Cantidad actualizada.',
            ];
        } catch (CartException $e) {
            $flash = [
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => 'No se pudo actualizar la cantidad.',
            ];

            Log::error('Failed to update quantity!', [
                'cart_id' => $cartId,
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('carts.index')
            ->with('flash', $flash);
    }

    public function incrementQuantity(int $cartId): RedirectResponse
    {
        try {
            $this->cartService->incrementQuantity(id: $cartId, userId: auth()->id());

            $flash = [
                'message' => 'Cantidad incrementada.',
            ];
        } catch (CartException $e) {
            $flash = [
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => 'No se pudo incrementar la cantidad.',
            ];

            Log::error('Failed to increment quantity!', [
                'cart_id' => $cartId,
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('carts.index')
            ->with('flash', $flash);
    }

    public function decrementQuantity(int $cartId): RedirectResponse
    {
        try {
            $this->cartService->decrementQuantity(id: $cartId, userId: auth()->id());

            $flash = [
                'message' => 'Cantidad reducida.',
            ];
        } catch (CartException $e) {
            $flash = [
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => 'No se pudo reducir la cantidad.',
            ];

            Log::error('Failed to decrement quantity!', [
                'cart_id' => $cartId,
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('carts.index')
            ->with('flash', $flash);
    }

    public function delete(int $cartId): RedirectResponse
    {
        try {
            $cart = $this->cartService->findByIdForUserOrFail(
                id: $cartId,
                userId: auth()->id()
            );
            $this->cartService->delete(cart: $cart);

            $flash = [
                'message' => 'Producto retirado de la venta.',
            ];
        } catch (CartNotFoundException $e) {
            $flash = [
                'isSuccess' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => 'No se pudo retirar el producto.',
            ];

            Log::error('Failed to delete cart item!', [
                'cart_id' => $cartId,
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('carts.index')
            ->with('flash', $flash);
    }

    public function deleteForUser(): RedirectResponse
    {
        try {
            $this->cartService->deleteForUser(auth()->id());

            $flash = [
                'message' => 'Venta vaciada.',
            ];
        } catch (Exception $e) {
            $flash = [
                'isSuccess' => false,
                'message' => 'No se pudo vaciar la venta.',
            ];

            Log::error('Failed to delete cart all items!', [
                'message' => $e->getMessage(),
                'traces' => $e->getTrace(),
            ]);
        }

        return redirect()
            ->route('carts.index')
            ->with('flash', $flash);
    }
}
