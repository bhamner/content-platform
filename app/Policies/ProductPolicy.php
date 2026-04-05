<?php

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(?User $user, Product $product): bool
    {
        if ($product->status === ProductStatus::Published) {
            return true;
        }

        return $user !== null && $user->id === $product->user_id;
    }

    public function create(User $user): bool
    {
        return $user->creatorProfile !== null;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function publish(User $user, Product $product): bool
    {
        if ($user->id !== $product->user_id) {
            return false;
        }

        $profile = $user->creatorProfile;

        return $profile !== null
            && $profile->canReceivePayments()
            && $product->files()->exists();
    }

    public function purchase(User $user, Product $product): bool
    {
        if ($product->status !== ProductStatus::Published) {
            return false;
        }

        if ($user->id === $product->user_id) {
            return false;
        }

        $profile = $product->creator?->creatorProfile;

        return $profile !== null && $profile->canReceivePayments();
    }

    public function download(User $user, Product $product): bool
    {
        return $user->purchases()
            ->where('product_id', $product->id)
            ->whereNull('revoked_at')
            ->exists();
    }
}
