<?php

declare(strict_types=1);

namespace FrancescoLaffi\PrimaryCategory;

class PrimaryCategoryAdmin
{
    private const REQUEST_FIELD = Constants\PRIMARY_CATEGORY_TAXONOMY;

    /** @var PrimaryCategoryRepository */
    private $repository;

    public function __construct(PrimaryCategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function init(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('save_post', [$this, 'savePost']);
    }

    public function enqueueAssets($hook): void
    {
        global $post;
        if (!$post instanceof \WP_Post || !\in_array($hook, ['post-new.php', 'post.php'], true)) {
            return;
        }

        $handle = 'primary_category_admin_js';
        wp_enqueue_script($handle, plugins_url('assets/primary-category.js', Constants\PATH), [], Constants\VERSION);

        $primaryCategoryId = $this->repository->findPostPrimaryCategory((int) $post->ID);
        wp_localize_script($handle, 'primaryCategoryData', [
            'primaryCategoryId' => $primaryCategoryId,
            'fieldName' => self::REQUEST_FIELD,
            'strPrimaryCategoryLabel' => __('Primary', Constants\TEXT_DOMAIN),
            'strPrimaryCategorySet' => __('Make primary', Constants\TEXT_DOMAIN),
        ]);
    }

    public function savePost($postId): void
    {
        $termId = \filter_input(INPUT_POST, self::REQUEST_FIELD, FILTER_SANITIZE_NUMBER_INT);
        if (null === $termId) {
            return;
        }

        $postId = (int) $postId;
        $termId = (int) $termId;

        if (!$termId) {
            $this->repository->clearPostPrimaryCategory($postId);

            return;
        }

        try {
            $this->repository->savePostPrimaryCategory($postId, $termId);
        } catch (\Exception $e) {
            $this->repository->clearPostPrimaryCategory($postId);
        }
    }
}
