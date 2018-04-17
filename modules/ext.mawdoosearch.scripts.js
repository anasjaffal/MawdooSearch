( function ( mw, $ ) {
    'use strict';


    $('.checkbox-result-item').on('change',function () {

        var textArea = $(this).parent('li').find('textarea');
        var is_checked = $(this).is(':checked');
        if(!is_checked)
        {
            textArea.prop('disabled',true);
            textArea.val('');
        }
        else
        {
            textArea.prop('disabled',false);
        }
        $(this).closest('search-result-item').children('textarea');
    });

    $('.delete-item').on('click',function () {

        var deleteDilogResult = confirm("Do you want to delete this item?");
        if (deleteDilogResult) {
            $('body').loading({
                theme: 'light'
            });
            var item = $(this).closest('li');
            var item_id = item.data('id');

            mw.loader.using('mediawiki.api', function () {
                ( new mw.Api() ).get({
                    action: 'delete_item',
                    id: item_id
                }).done(function (data) {
                    $('body').loading('stop');
                    item.remove();
                });
            });
        }
    });


    $('.update-item').on('click',function () {

            $('body').loading({
                theme: 'light'
            });

            var item = $(this).closest('li');
            var item_id = item.data('id');
            var item_comment = item.find('textarea');
            mw.loader.using('mediawiki.api', function () {
                ( new mw.Api() ).post({
                    action: 'update_item',
                    id: item_id,
                    comment:item_comment.val()
                }).done(function (data) {

                    $('body').loading('stop');
                    console.log("data",data);

                });
            });

    });
}( mediaWiki, jQuery ) );