<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



Auth::routes();
// index Routes
Route::get('/', [HomeController::class, 'index'])->name('home.index');


// Auth Routes
Route::middleware(['auth'])->group(function (){
    // User Routes:
    Route::get('/account-dashboard', [UserController::class, 'index'])->name('user.index');
});
#----------------------------------------------------------------------------------------------------------------------------------------------------------------
// Admin panal Routes
Route::middleware(['auth', AuthAdmin::class])->group(function (){
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    // Brands Routes:
    Route::get('/admin/brands', [AdminController::class,'brands'])->name('admin.brands');
    Route::get('/admin/brands/add',[AdminController::class,'add_brands'])->name('admin.brands.add');
    Route::post('/admin/brands/store',[AdminController::class,'brands_store'])->name('admin.brands.store');
    Route::get('/admin/brands/edit/{id}',[AdminController::class,'brands_edit'])->name('admin.brands.edit');
    Route::put('/admin/brands/update',[AdminController::class,'brands_update'])->name('admin.brands.update');
    Route::delete('/admin/brands/{id}/delete',[AdminController::class,'brands_delete'])->name('admin.brands.delete');

    // Categories Routes:
    Route::get('/admin/categories',[AdminController::class,'categories'])->name('admin.categories');
    Route::get("/admin/category/add",[AdminController::class,"category_add"])->name("admin.category.add");
    Route::post('/admin/category/store',[AdminController::class,'category_store'])->name('admin.category.store');
    Route::get('/admin/category/edit/{id}',[AdminController::class,'category_edit'])->name('admin.category.edit');
    Route::put('/admin/category/update',[AdminController::class,'category_update'])->name('admin.category.update');
    Route::delete('/admin/category/{id}/delete',[AdminController::class,'category_delete'])->name('admin.category.delete');

    // Products Routes:
    Route::get("/admin/products",[AdminController::class,'products'])->name('admin.products');
    Route::get('/admin/products/add',[AdminController::class, 'product_add'])->name('admin.product.add');
    Route::post('/admin/products/store',[AdminController::class,'product_store'])->name('admin.product.store');
    Route::get('/admin/products/edit/{id}',[AdminController::class,'product_edit'])->name('admin.product.edit');
    Route::put('/admin/products/update',[AdminController::class,'product_update'])->name('admin.product.update');
    Route::delete('/admin/products/{id}/delete',[AdminController::class,'product_delete'])->name('admin.product.delete');
    // Coupons Route
    Route::get('/admin/coupons',[AdminController::class,'coupons'])->name('admin.coupons');
    Route::get('/admin/coupons/add',[AdminController::class,'coupon_add'])->name('admin.coupon.add');
    Route::post('/admin/coupons/store',[AdminController::class,'coupon_store'])->name('admin.coupon.store');
    Route::get('/admin/coupons/{id}/edit',[AdminController::class,'coupon_edit'])->name('admin.coupon.edit');
    Route::put('/admin/coupons/update',[AdminController::class,'coupon_update'])->name('admin.coupon.update');
    Route::delete('/admin/coupons/{id}/delete',[AdminController::class,'coupon_delete'])->name('admin.coupon.delete');
    // Orders Route
    Route::get('/admin/orders',[AdminController::class,'orders'])->name('admin.orders');
#----------------------------------------------------------------------------------------------------------------------------------------------------------------
    //  Shop Routes
    Route::get('/shop',[ShopController::class,'index'])->name('shop.index');
    Route::get('/shop/{product_slug}',[ShopController::class,'product_details'])->name('shop.product.details');
#----------------------------------------------------------------------------------------------------------------------------------------------------------------
    //  Cart Routes
    Route::get('/cart',[CartController::class,'index'])->name('cart.index');
    Route::post('/cart/add',[CartController::class,'add_to_cart'])->name('cart.add');
    Route::put('/cart/increase-quantity/{rowId}',[CartController::class,'increase_cart_quantity'])->name('cart.qty.increase');
    Route::put('/cart/decrease-quantity/{rowId}',[CartController::class,'decrease_cart_quantity'])->name('cart.qty.decrease');
    Route::delete('/cart/remove/{rowId}',[CartController::class,'remove_item'])->name('cart.item.remove');
    Route::delete('/cart/clear',[CartController::class,'empty_cart'])->name('cart.empty');
    Route::post('/cart/apply-coupon',[CartController::class,'apply_coupon_code'])->name('cart.coupon.apply');
    Route::delete('/cart/remove-coupon',[CartController::class,'remove_coupon_code'])->name('cart.coupon.remove');
#----------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Wishlist Routes
    Route::get('/wishlist',[WishlistController::class,'index'])->name('wishlist.index');
    Route::post('/wishlist/add',[WishlistController::class, 'add_to_wishlist'])->name('wishlist.add');
    Route::delete('/wishlist/item/remove/{rowId}',[WishlistController::class,'remove_item'])->name('wishlist.item.remove');
    Route::delete('/wishlist/clear',[WishlistController::class,'empty_wishlist'])->name('wishlist.items.clear');
    Route::post('/wishlist/move-to-cart/{rowId}',[WishlistController::class,'move_to_cart'])->name('wishlist.move.to.cart');
#----------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Checkout Route
    Route::get('/checkout',[CartController::class,'checkout'])->name('cart.checkout');
    Route::post('/place-an-order', [CartController :: class,'place_an_order'])->name('cart.place.an.order');
    Route::get('/order-confirmation',[CartController::class,'order_confirmation'])->name('cart.order.confirmation');
#----------------------------------------------------------------------------------------------------------------------------------------------------------------

});
