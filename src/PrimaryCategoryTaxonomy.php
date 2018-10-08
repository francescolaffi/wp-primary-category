<?php

declare(strict_types=1);

namespace FrancescoLaffi\PrimaryCategory;

class PrimaryCategoryTaxonomy
{
    /** @var PrimaryCategoryRepository */
    private $repository;

    public function __construct(PrimaryCategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        $postTypes = get_taxonomy('category')->object_type;
        register_taxonomy(Constants\PRIMARY_CATEGORY_TAXONOMY, $postTypes, [
            'public' => false,
            'query_var' => false,
        ]);

        // avoid pre-loading term meta for primary category terms
        add_filter('get_terms_args', function (array $args, $taxonomies): array {
            if ($taxonomies === [Constants\PRIMARY_CATEGORY_TAXONOMY]) {
                $args['update_term_meta_cache'] = false;
            }

            return $args;
        }, 10, 2);

        // ensure data consistency when categories are removed from posts or deleted
        add_action('set_object_terms', function ($postId, $terms, $ttIds, $taxonomy): void {
            if ('category' === $taxonomy) {
                $this->repository->ensurePostPrimaryCategoryConsistency((int) $postId);
            }
        }, 10, 4);
        add_action('deleted_term_relationships', function ($postId, $ttIds, $taxonomy): void {
            if ('category' === $taxonomy) {
                $this->repository->ensurePostPrimaryCategoryConsistency((int) $postId);
            }
        }, 10, 3);
        add_action('delete_category', function ($termId): void {
            $this->repository->deletePrimaryCategory((int) $termId);
        });

        // use primary category in permalinks
        add_filter('post_link_category', function (\WP_Term $category, array $categories, \WP_Post $post): \WP_Term {
            /** @var \WP_Term[] $categories */
            if (1 === \count($categories)) {
                return $category;
            }

            $primaryCategoryId = $this->repository->findPostPrimaryCategory((int) $post->ID);
            foreach ($categories as $cat) {
                if ($primaryCategoryId === (int) $cat->term_id) {
                    return $cat;
                }
            }

            return $category;
        }, 10, 3);
    }
}
