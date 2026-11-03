<?php
class Review extends Model
{
    public function exists(int $deliveryId, int $reviewerId): bool
    {
        return (bool)$this->val('SELECT COUNT(*) FROM reviews WHERE delivery_id = ? AND reviewer_id = ?', [$deliveryId, $reviewerId]);
    }

    public function create(int $deliveryId, int $reviewerId, int $revieweeId, int $rating, string $comment): void
    {
        $this->exec('INSERT INTO reviews (delivery_id, reviewer_id, reviewee_id, rating, comment) VALUES (?,?,?,?,?)',
            [$deliveryId, $reviewerId, $revieweeId, $rating, $comment ?: null]);
    }

    public function forDelivery(int $deliveryId): array
    {
        return $this->rows('SELECT r.*, u.name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.delivery_id = ?', [$deliveryId]);
    }
}
