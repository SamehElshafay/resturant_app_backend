<?php

namespace App\Http\Controllers;

use App\Models\CouponModels\Coupons;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use TransactionResponse ;

    public function index() {
        return Coupons::with(['products'])->latest()->get();
    }

    public function store(Request $request) {
        return $this->transactionResponse(function () use ($request) {
            $coupon = Coupons::create($request->all());
            return $coupon ;
        });
    }

    public function show($id) {
        return Coupons::findOrFail($id);
    }

    public function update(Request $request, $id) {
        return $this->transactionResponse(function () use ($request, $id) {

            $coupon = Coupons::findOrFail($id);
            $coupon->update($request->all());

            return $coupon ;
        });
    }

    public function destroy($id) {
        return $this->transactionResponse(function () use ($id) {
            Coupons::findOrFail($id)->delete();
            return true;
        });
    }
}


/*-- ===============================
-- Table: coupons (with description)
-- ===============================
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    type ENUM('fixed','percent') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) NULL,
    max_discount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    usage_per_user INT NULL,
    used_count INT NOT NULL DEFAULT 0,
    start_at DATETIME NOT NULL,
    expire_at DATETIME NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===============================
-- Table: coupon_usages
-- ===============================
CREATE TABLE coupon_usages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    customer_id INT NOT NULL,
    order_id INT NULL,
    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_coupon_usages_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_coupon_usages_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE
);

-- ===============================
-- Table: coupon_commercial_places
-- ===============================
CREATE TABLE coupon_commercial_places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    commercial_place_id INT NOT NULL,
    CONSTRAINT fk_ccp_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ccp_commercial_place
        FOREIGN KEY (commercial_place_id)
        REFERENCES commercial_places(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_coupon_commercial (coupon_id, commercial_place_id)
);

-- ===============================
-- Table: coupon_products
-- ===============================
CREATE TABLE coupon_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    product_id INT NOT NULL,
    CONSTRAINT fk_coupon_products_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_coupon_products_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_coupon_product (coupon_id, product_id)
);*/