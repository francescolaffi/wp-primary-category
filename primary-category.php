<?php

declare(strict_types=1);

/*
Plugin Name:  Primary Category
Version:      0.1.0
Author:       Francesco Laffi
Text Domain:  primary-category
Domain Path:  /languages
*/

namespace FrancescoLaffi\PrimaryCategory\Constants {
    const VERSION = '0.1.0';
    const PATH = __FILE__;
    const TEXT_DOMAIN = 'primary-category';
    const PRIMARY_CATEGORY_TAXONOMY = 'primary_category';
}

namespace FrancescoLaffi\PrimaryCategory {
    (function (): void {
        $autoload = __DIR__.'/vendor/autoload.php';
        if (\is_file($autoload)) {
            include_once $autoload;
        }

        $repository = new PrimaryCategoryRepository();
        add_action('init', function () use ($repository): void {
            load_plugin_textdomain(Constants\TEXT_DOMAIN, false, \basename(__DIR__).'/languages');

            (new PrimaryCategoryTaxonomy($repository))->register();
            $query = new PostsByPrimaryCategoryQuery();
            (new PostsByPrimaryCategoryShortcode($query))->register();
        });
        add_action('admin_init', function () use ($repository): void {
            (new PrimaryCategoryAdmin($repository))->init();
        });
    })();
}
