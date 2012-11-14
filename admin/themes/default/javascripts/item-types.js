if (!Omeka) {
    var Omeka = {};
}

Omeka.ItemTypes = {};

(function ($) {
    /**
     * Enable drag and drop sorting for elements.
     */
    Omeka.ItemTypes.enableSorting = function () {
        $('.sortable').sortable({
            items: 'li.element',
            forcePlaceholderSize: true,
            forceHelperSize: true,
            placeholder: 'ui-sortable-highlight',
            update: function (event, ui) {
                $(this).find('.element-order').each(function (index) {
                    $(this).val(index + 1);
                });
            }
        });
    };

    /**
     * Add link that collapses and expands content.
     */
    Omeka.ItemTypes.addHideButtons = function () {
        $('.sortable .drawer-contents').each(function () {
            if( $(this).prev().hasClass("sortable-item") ) {
                $(this).hide();
            }
        });
        $('div.sortable-item').each(function () {
            $(this).append('<div class="drawer"></div>');
        });
        $('.drawer')
            .click(function (event) {
                event.preventDefault();
                $(event.target).parent().next().toggle();
                $(this).toggleClass('opened');
            })
            .mousedown(function (event) {
                event.stopPropagation();
            });
    };

    /**
     * Add AJAX-enabled buttons to item type form for adding and removing elements.
     *
     * @param {string} addNewRequestUrl
     * @param {string} addExistingRequestUrl
     * @param {string} changeExistingElementUrl
     */
    Omeka.ItemTypes.manageItemTypes = function (addNewRequestUrl, addExistingRequestUrl, changeExistingElementUrl) {
        /**
         * Activate dropdown for selecting from existing elements.
         */
        function activateSelectElementDropdowns() {
            $('select.existing-element-drop-down').change(function () {
                var dropDown = $(this);
                var elementId = dropDown.val();
                var addExistingElementIdPrefix = 'add-existing-element-id-';
                var addExistingElementId = this.getAttribute('id');
                if (addExistingElementId) {
                    var elementTempId = addExistingElementId.substring(addExistingElementIdPrefix.length);
                    $.ajax({
                        url: changeExistingElementUrl,
                        dataType: 'json',
                        data: {elementId: elementId, elementTempId: elementTempId},
                        success: function (response) {
                            var elementDescriptionCol = dropDown.parent().next();
                            elementDescriptionCol.html(response.elementDescription);
                        },
                        error: function () {
                            alert('Unable to get selected element data.');
                        }
                    });
                }
            });
        }
        
        /**
         * Turn all the links into AJAX requests that will mark the element for deletion and update the list.
         */
        function activateRemoveElementLinks() {
            $('a.delete-element').click(function (event) {
                event.preventDefault();
                var elementsToRemove = $('#itemtypes_remove');

                var removeElementLinkPrefix = 'remove-element-link-';
                var removeElementLinkId = this.getAttribute('id');
                if (removeElementLinkId) {
                    var elementId = removeElementLinkId.substring(removeElementLinkPrefix.length);
                    if (elementId) {
                        if (!confirm('Are you sure you want to delete this element? This will remove the element from this particular item type. Items that are assigned to this item type will lose metadata that is specific to this element.')) {
                            return;
                        }
                        elementsToRemove.attr('value', elementsToRemove.attr('value') + elementId + ',');
                    }
                }
                var row = $(this).parent().parent();
                row.remove();
            });
        }

        $('#add-element').click(function (event) {
            event.preventDefault();
            var elementCount = $('#item-type-elements li').length;
            var typeValue = $('input[name=add-element-type]:checked').val();
            var requestUrl;
            if (typeValue === 'new') {
                requestUrl = addNewRequestUrl;
            } else {
                requestUrl = addExistingRequestUrl;
            }
            $.ajax({
                url: requestUrl,
                dataType: 'text',
                data: {elementCount: elementCount},
                success: function (responseText) {
                    var response = responseText || 'no response text';
                    var lastElement = $('.element').last();
                    lastElement.after(response);
                    activateRemoveElementLinks();
                    activateSelectElementDropdowns();
                },
                error: function () {
                    alert('Unable to get a new element.');
                }
            });
        });

        activateRemoveElementLinks();
    };
})(jQuery);
