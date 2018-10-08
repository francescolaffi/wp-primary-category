<?php

declare(strict_types=1);

namespace FrancescoLaffi\PrimaryCategory;

class PrimaryCategoryRepository
{
    /**
     * Find the primary category for the given post.
     *
     * @param int $postId
     *
     * @return int|null
     */
    public function findPostPrimaryCategory(int $postId): ?int
    {
        $primaryCategoryId = $this->findTermIdByPost($postId);
        if (!$primaryCategoryId || !$this->isPostCategory($postId, $primaryCategoryId)) {
            return null;
        }

        return $primaryCategoryId;
    }

    /**
     * Save a category as primary for the given post.
     *
     * @param int $postId
     * @param int $categoryId
     *
     * @throws \InvalidArgumentException for invalid category
     * @throws \RuntimeException         for failure persisting the primary category
     */
    public function savePostPrimaryCategory(int $postId, int $categoryId): void
    {
        if (!$this->isPostCategory($postId, $categoryId)) {
            throw new \RuntimeException(\sprintf('term `%d` is not a category for post `%d`', $categoryId, $postId));
        }

        // wp_set_object_terms also removes any term previously associated as primary category
        $termName = (string) $categoryId;
        $affected = wp_set_object_terms($postId, $termName, Constants\PRIMARY_CATEGORY_TAXONOMY);
        if (!\is_array($affected) || 1 !== \count($affected)) {
            throw new \RuntimeException(\sprintf('could not save term `%d` as primary category for post `%d`', $categoryId, $postId));
        }
    }

    /**
     * Clear the primary category for the given post.
     *
     * @param int $postId
     */
    public function clearPostPrimaryCategory(int $postId): void
    {
        wp_delete_object_term_relationships($postId, Constants\PRIMARY_CATEGORY_TAXONOMY);
    }

    /**
     * Ensure the primary category consistent with the categories for the given post, or clears it.
     *
     * @param int $postId
     */
    public function ensurePostPrimaryCategoryConsistency(int $postId): void
    {
        $primaryCategoryId = $this->findTermIdByPost($postId);
        if ($primaryCategoryId && !$this->isPostCategory($postId, $primaryCategoryId)) {
            $this->clearPostPrimaryCategory($postId);
        }
    }

    public function deletePrimaryCategory(int $categoryId): void
    {
        $termsIds = (new \WP_Term_Query())->query([
            'taxonomy' => Constants\PRIMARY_CATEGORY_TAXONOMY,
            'name' => (string) $categoryId,
            'hide_empty' => false,
            'fields' => 'ids',
            'orderby' => 'none',
            'update_term_meta_cache' => false,
        ]);
        foreach ($termsIds as $termId) {
            wp_delete_term($termId, Constants\PRIMARY_CATEGORY_TAXONOMY);
        }
    }

    private function findTermIdByPost(int $postId): ?int
    {
        $terms = get_the_terms($postId, Constants\PRIMARY_CATEGORY_TAXONOMY);
        if (!\is_array($terms) || !isset($terms[0]) || !$terms[0] instanceof \WP_Term) {
            return null;
        }

        return (int) $terms[0]->name;
    }

    private function isPostCategory(int $postId, int $termId): bool
    {
        $categories = get_the_terms($postId, 'category');
        if (!\is_array($categories)) {
            return false;
        }
        /** @var \WP_Term $category */
        foreach ($categories as $category) {
            if ($termId === (int) $category->term_id) {
                return true;
            }
        }

        return false;
    }
}
