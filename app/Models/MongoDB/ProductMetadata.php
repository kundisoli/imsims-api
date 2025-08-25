<?php

namespace App\Models\MongoDB;

use MongoDB\Laravel\Eloquent\Model;

class ProductMetadata extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'product_metadata';

    protected $fillable = [
        'product_id',
        'images',
        'tags',
        'attributes',
        'specifications',
        'variants',
        'seo_data',
        'custom_fields',
        'supplier_info',
        'certifications',
        'warnings',
        'storage_requirements'
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
        'attributes' => 'array',
        'specifications' => 'array',
        'variants' => 'array',
        'seo_data' => 'array',
        'custom_fields' => 'array',
        'supplier_info' => 'array',
        'certifications' => 'array',
        'warnings' => 'array',
        'storage_requirements' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get metadata by product ID
     */
    public static function getByProduct(int $productId)
    {
        return static::where('product_id', $productId)->first();
    }

    /**
     * Update or create metadata for a product
     */
    public static function updateOrCreateForProduct(int $productId, array $metadata)
    {
        return static::updateOrCreate(
            ['product_id' => $productId],
            $metadata
        );
    }

    /**
     * Add image to product
     */
    public function addImage(array $imageData): void
    {
        $images = $this->images ?? [];
        $images[] = array_merge($imageData, [
            'id' => uniqid(),
            'uploaded_at' => now()->toISOString()
        ]);
        
        $this->update(['images' => $images]);
    }

    /**
     * Remove image from product
     */
    public function removeImage(string $imageId): void
    {
        $images = $this->images ?? [];
        $images = array_filter($images, fn($img) => $img['id'] !== $imageId);
        
        $this->update(['images' => array_values($images)]);
    }

    /**
     * Add tag to product
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    /**
     * Remove tag from product
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        
        $this->update(['tags' => array_values($tags)]);
    }

    /**
     * Set custom field
     */
    public function setCustomField(string $key, $value): void
    {
        $customFields = $this->custom_fields ?? [];
        $customFields[$key] = $value;
        
        $this->update(['custom_fields' => $customFields]);
    }

    /**
     * Get custom field value
     */
    public function getCustomField(string $key, $default = null)
    {
        $customFields = $this->custom_fields ?? [];
        return $customFields[$key] ?? $default;
    }

    /**
     * Search products by tags
     */
    public static function searchByTags(array $tags)
    {
        return static::whereIn('tags', $tags)->get();
    }

    /**
     * Search products by custom fields
     */
    public static function searchByCustomField(string $key, $value)
    {
        return static::where("custom_fields.{$key}", $value)->get();
    }
}