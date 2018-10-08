Earlier in 2018 I implemented this small demo project for a company I was considering collaborating with, this codebase is not maintained and published only for backup and information purpose.

The project requirments are in [assignment.png](./assignment.png); following below analysis and explainations.

-----

Using this file for noting though process, so its more of an append-only log then a actual readme

Considerations on the assignment:
- performance: need to find the optimal way to store the info to optimize read performance, i.e. querying posts by primary category; 
  leaving aside changing the db schema, the two possibilities I see for storage are: a post meta w/ primary category id,
  or a custom taxonomy relating posts w/ category terms; my first bet is on the custom taxonomy, I'll see if I can verify it. 
- NTH: if I manage to proceed fast enough I'd like to, even if not required:
    - add some tests
    - ensure that when category is used in the permalink structure, then the primary category is used to generate urls
- edit UI: I'd like to customize the category metabox to choose the primary category between those assigned to the post,
  will definitely need some client side logic as categories can be managed in different ways in the metabox and even created directly.
- categories and primary category consistency: I assume a category can be primary only if its a assigned as category for the post, 
  meaning also that whenever post categories changes (removing categories from a post, deleting a category), 
  the primary category might be invalid and must be cleared for consistency.

After a quick check here is what WP_Query produce when filtering by post meta or taxonomy id:
```
EXPLAIN SELECT   wp_posts.ID FROM wp_posts  INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id ) WHERE 1=1  AND (   ( wp_postmeta.meta_key = 'primary_category' AND wp_postmeta.meta_value = '123' ) ) AND wp_posts.post_type = 'post' AND (wp_posts.post_status = 'publish' OR wp_posts.post_status = 'private') GROUP BY wp_posts.ID ORDER BY wp_posts.post_date DESC LIMIT 0, 10;

EXPLAIN SELECT  t.*, tt.* FROM wp_terms AS t  INNER JOIN wp_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('primary_category') AND t.term_id IN ( 1 );
EXPLAIN SELECT   wp_posts.ID FROM wp_posts  LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id) WHERE 1=1  AND (   wp_term_relationships.term_taxonomy_id IN (1) ) AND wp_posts.post_type = 'post' AND (wp_posts.post_status = 'publish' OR wp_posts.post_status = 'private') GROUP BY wp_posts.ID ORDER BY wp_posts.post_date DESC LIMIT 0, 10
```
Although it uses two queries, the approach by taxonomy leverage indexes for conditions on `wp_term_taxonomy` and `wp_term_relationships`, while the post meta approach does not leverage any index to filter by meta value, so I'm going ahead with custom taxonomy.
Of course the optimal solution should be always analyzed on the real use cases with realistic data, and could involve introducing different datastores more adapt to indexing denormalized data when complex queries on a lot of data are needed.

For the sake of the exercise I'm assuming the context is a custom plugin for some client sites with a known modern environment,
so I'll using a modern setup I already know well, leveraging trellis+bedrock.
If the plugin needed to be distributed publicly some restriction would be needed, e.g. backward compat with php and WP versions.
Leaving everything related to the dev environment in ./vm/ for reference, its configured to run the site 'primary-category.test',
`cd ./vm/ && vagrant up` should be enough to run the local dev environment.

Adding `PrimaryCategoryRepository` as storage abstraction, and `PrimaryCategoryTaxonomy` to manage the custom taxonomy.
Adding `PrimaryCategoryAdmin` and `assets/primary-category.js` to customize the admin category meta box and handle saving of primary category.
Depending on the complexity of the client side, I'd use an appropriate toolchain for it and have better separation of logic/styling, for the current complexity 
its fine by me just a single js file without any processing.

Found some problems with my first approach with a custom taxonomy that used the same term rows for both category and primary category associations,
as relating the same terms with multiple taxonomy ends up in the wp error `ambiguous_term_id` in situations such as editing/deleting categories, 
this is a issue derived from calling `get_term` w/o a taxonomy as it is in a few cases in WP codebase.
To overcome this shortcoming I'm rewriting `PrimaryCategoryRepository` to store the primary category in a separate term, 
with the category id as name, luckily the persistence logic is well incapsulated in the repository and wont need to change any other logic.
Regarding read-performance its still a good choice, although it leverages the index on term name
instead of the one on term id, still better than search on post meta value.

Added logic to `PrimaryCategoryTaxonomy` to make sure primary category is consistent with post categories,
when post categories are updated removed or when a category is deleted.

To demonstrate the ability to query by primary category I added a simple shortcode to render a list of post by primary category,
`[primary_category_posts slug=my-category]` or `[primary_category_posts id=123]`, handled `PostsByPrimaryCategoryShortcode` and `PostsByPrimaryCategoryQuery`

Added hook to ensure the primary category is used in permalinks.

Profiled main uses cases, in particular read performance and queries:
- refactored repository to use higher level apis that leverage cache (`get_the_terms` instead of )
- ensured that when primary categories are loaded there is no additional query to load useless term meta
- ensured that when post queries preload terms cache, primary categories are preloaded if relevant (configured taxonomy to with same post types as category taxonomy) 

Hoped to get some time to add tests, but its not going to happen anytime soon, so I'm submitting it as-is.

Final checks, cleanup.