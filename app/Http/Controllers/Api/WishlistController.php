<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\ConfirmPurchaseWishlistRequest;
use App\Http\Requests\Wishlist\StoreWishlistRequest;
use App\Http\Resources\WishlistResource;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;


class WishlistController extends Controller
{
    public function __construct(
        protected WishlistService $wishlistService
    ) {}

    #[OA\Get(
        path: '/api/wishlists',
        summary: 'Ambil daftar wishlist dan total saldo tabungan wishlist',
        description: 'Mengambil daftar wishlist (aktif, diarsipkan, atau dibeli) beserta total saldo tabungan wishlist. Mendukung pengurutan dan otomatis menyinkronkan masa tunda yang telah berakhir.',
        tags: ['Wishlist'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'tab',
                in: 'query',
                required: false,
                description: 'Filter tab: active (delayed & pending_decision), archived, bought',
                schema: new OA\Schema(type: 'string', enum: ['active', 'archived', 'bought'])
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                required: false,
                description: 'Pengurutan daftar wishlist',
                schema: new OA\Schema(type: 'string', enum: ['latest', 'oldest', 'price_asc', 'price_desc', 'delay_asc', 'delay_desc', 'progress_desc'])
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data wishlist berhasil diambil.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $tab = $request->query('tab');
        $sort = $request->query('sort');

        $result = $this->wishlistService->getAll($tab, $sort);

        return WishlistResource::collection($result['items'])
            ->additional([
                'total_wishlist_savings' => $result['total_wishlist_savings'],
                'message' => 'Data wishlist berhasil diambil.',
            ])
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Post(
        path: '/api/wishlists',
        summary: 'Tambah wishlist baru',
        description: 'Membuat wishlist baru dengan masa tunda (default 7 hari) dan status awal delayed.',
        tags: ['Wishlist'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['item_name', 'price'],
                properties: [
                    new OA\Property(property: 'item_name', type: 'string', maxLength: 200, example: 'Headphone Nirkabel'),
                    new OA\Property(property: 'purchase_url', type: 'string', nullable: true, example: 'https://tokopedia.com/contoh-produk'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 1200000),
                    new OA\Property(property: 'delay_days', type: 'integer', nullable: true, example: 7),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Tunggu gajian bulan depan'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Wishlist berhasil ditambahkan.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 422, description: 'Validasi gagal.'),
        ]
    )]
    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $wishlist = $this->wishlistService->create($request->validated());

        return (new WishlistResource($wishlist))
            ->additional([
                'message' => 'Wishlist berhasil ditambahkan.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/wishlists/{id}',
        summary: 'Lihat detail wishlist',
        tags: ['Wishlist'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID wishlist', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail wishlist berhasil diambil.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 404, description: 'Wishlist tidak ditemukan.'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $wishlist = $this->wishlistService->getById($id);

        return (new WishlistResource($wishlist))
            ->additional([
                'message' => 'Detail wishlist berhasil diambil.',
            ])
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/api/wishlists/{id}',
        summary: 'Hapus wishlist',
        tags: ['Wishlist'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID wishlist', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wishlist berhasil dihapus.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 404, description: 'Wishlist tidak ditemukan.'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $this->wishlistService->delete($id);

        return ResponseHelper::success(null, 'Wishlist berhasil dihapus.');
    }

    #[OA\Post(
        path: '/api/wishlists/{id}/archive',
        summary: 'Arsipkan wishlist dan jeda hitung mundur',
        description: 'Memindahkan wishlist ke arsip dan menjeda waktu hitung mundur masa tunda.',
        tags: ['Wishlist'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID wishlist', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wishlist berhasil diarsipkan.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 404, description: 'Wishlist tidak ditemukan.'),
            new OA\Response(response: 422, description: 'Wishlist tidak dapat diarsipkan.'),
        ]
    )]
    public function archive(string $id): JsonResponse
    {
        $wishlist = $this->wishlistService->archive($id);

        return (new WishlistResource($wishlist))
            ->additional([
                'message' => 'Wishlist berhasil diarsipkan.',
            ])
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Post(
        path: '/api/wishlists/{id}/unarchive',
        summary: 'Aktifkan kembali wishlist dari arsip',
        description: 'Mengembalikan wishlist dari arsip ke daftar aktif dan melanjutkan hitung mundur masa tunda.',
        tags: ['Wishlist'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID wishlist', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wishlist berhasil diaktifkan kembali.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 404, description: 'Wishlist tidak ditemukan.'),
            new OA\Response(response: 422, description: 'Wishlist bukan berstatus arsip.'),
        ]
    )]
    public function unarchive(Request $request, string $id): JsonResponse
    {
        $delayDays = $request->input('delay_days') ? (int) $request->input('delay_days') : null;
        $wishlist = $this->wishlistService->unarchive($id, $delayDays);

        return (new WishlistResource($wishlist))
            ->additional([
                'message' => 'Wishlist berhasil diaktifkan kembali.',
            ])
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Post(
        path: '/api/wishlists/{id}/confirm-purchase',
        summary: 'Konfirmasi pembelian wishlist setelah masa tunda berakhir',
        description: 'Jika purchased=true, saldo tabungan wishlist dikurangi sesuai harga barang dan status menjadi bought. Jika purchased=false, wishlist dipindahkan ke arsip.',
        tags: ['Wishlist'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID wishlist', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['purchased'],
                properties: [
                    new OA\Property(property: 'purchased', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Keputusan pembelian wishlist berhasil diproses.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 404, description: 'Wishlist tidak ditemukan.'),
            new OA\Response(response: 422, description: 'Wishlist belum siap dibeli atau saldo kurang.'),
        ]
    )]
    public function confirmPurchase(ConfirmPurchaseWishlistRequest $request, string $id): JsonResponse
    {
        $wishlist = $this->wishlistService->confirmPurchase($id, (bool) $request->validated('purchased'));

        return (new WishlistResource($wishlist))
            ->additional([
                'message' => $request->validated('purchased')
                    ? 'Pembelian wishlist berhasil dikonfirmasi.'
                    : 'Wishlist dipindahkan ke arsip.',
            ])
            ->response()
            ->setStatusCode(200);
    }
}

