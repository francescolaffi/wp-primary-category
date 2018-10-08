<?php

declare(strict_types=1);

namespace FrancescoLaffi\PrimaryCategory;

class PostsByPrimaryCategoryQuery
{
    public function queryPostsByPrimaryCategory(int $categoryId, array $args = []): \WP_Query
    {
        $args = \array_replace($args, $this->primaryCategoryQueryArgs($categoryId));

        return new \WP_Query($args);
    }

    public function primaryCategoryQueryArgs(int $categoryId): array
    {
        return ['tax_query' => [[
            'taxonomy' => Constants\PRIMARY_CATEGORY_TAXONOMY,
            'field' => 'name',
            'terms' => (string) $categoryId,
        ]]];
    }
}
