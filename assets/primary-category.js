jQuery(function ($) {
    var primaryCategory = window.primaryCategoryData.primaryCategoryId;

    var $categoryChecklist = $('#categorychecklist');
    if (!$categoryChecklist.length) {
       return;
    }

    var $inputPrimaryCategory = $('<input type="hidden">').attr('name', window.primaryCategoryData.fieldName);
    $inputPrimaryCategory.insertAfter($categoryChecklist);

    // idempotent render of all DOM elements for primary category actions, from source of truth (local var)
    function render() {
        $inputPrimaryCategory.val(primaryCategory || 0);
        $categoryChecklist.find('.primary-category-label, .primary-category-set').remove();

        $categoryChecklist.find('li').each(function () {
           var $item = $(this),
               $label = $item.children('label'),
               $checkbox = $label.children('input[type=checkbox]'),
               id = $checkbox.val()
           ;

           if (!$checkbox.prop('checked')) {
               return;
           }

           if (id === primaryCategory) {
               $('<strong class="primary-category-label" style="float: right;"></strong>')
                   .text(window.primaryCategoryData.strPrimaryCategoryLabel).insertBefore($label);
               return;
           }

           $('<a class="primary-category-set" style="float: right;" href="#"></a>')
               .data('id', id).text(window.primaryCategoryData.strPrimaryCategorySet).insertBefore($label);
        });
    }

    // when a category is checked/unchecked in either all or popular list
    $categoryChecklist.add('#categorychecklist-pop').on('change', 'input[type=checkbox]', function (event) {
        var $checkbox = $(event.target);
        // unset primary category when its category is unchecked
        if ($checkbox.val() === primaryCategory && !$checkbox.prop('checked')) {
            primaryCategory = null;
        }
        render();
    });
    // when a new category is added
    $categoryChecklist.on('wpListAddEnd', render);
    // when a category is set as primary
    $categoryChecklist.on('click', '.primary-category-set', function(event) {
        event.preventDefault();
        primaryCategory = $(this).data('id');
        render();
    });

    render();
});
