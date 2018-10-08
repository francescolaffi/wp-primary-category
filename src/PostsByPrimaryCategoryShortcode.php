<?php

declare(strict_types=1);

namespace FrancescoLaffi\PrimaryCategory;

class PostsByPrimaryCategoryShortcode
{
    public const NAME = 'primary_category_posts';

    /** @var PostsByPrimaryCategoryQuery */
    private $query;

    public function __construct(PostsByPrimaryCategoryQuery $query)
    {
        $this->query = $query;
    }

    public function register(): void
    {
        add_shortcode(self::NAME, [$this, 'render']);
    }

    public function render($attributes): string
    {
        $attributes = shortcode_atts([
            'id' => null,
            'slug' => null,
        ], $attributes);

        if ($attributes['id']) {
            $categoryId = (int) $attributes['id'];
        } elseif ($attributes['slug'] && ($category = get_term_by('slug', $attributes['slug'], 'category')) instanceof \WP_Term) {
            $categoryId = $category->term_id;
        } else {
            return '';
        }

        /** @var \WP_Post[] $posts */
        $posts = $this->query->queryPostsByPrimaryCategory($categoryId, [
            'update_post_meta_cache' => false,
            'no_found_rows' => true,
        ])->posts;

        if (!$posts) {
            return '';
        }

        $html = '<ul>';
        foreach ($posts as $post) {
            $html .= \sprintf('<li><a href="%s">%s</a></li>', esc_url(get_permalink($post)), get_the_title($post));
        }
        $html .= '</ul>';

        return $html;
    }
}
