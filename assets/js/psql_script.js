jQuery(document).ready(function($){

    function syncDirectPostProfiles()
    {
        let dProfiles = [];
        let aProfiles = $('.psl_meta_nt_box').length;

        $('.psl_meta_nt_box.selected').each(function()
        {
            let data_id = $(this).attr('data-id');

            dProfiles.push(data_id);
        });

        if(dProfiles.length === 0)
        {
            $('#psl_select_all_networks').prop("checked",false);
        }
        else if(dProfiles.length === aProfiles)
        {
            $('#psl_select_all_networks').prop("checked",true);
        }

        $.ajax({
            url: obj.ajaxurl,
            method: "POST",
            data: {
                profiles: dProfiles,
                action: "sync_psql_dp_profiles"
            }
        });
    }

    function removeSiteData()
    {
        let bt = $("#psl_disconnect_key");
        let pre_val = bt.val();

        bt.val("Please wait...").attr('disabled',true);

        $.ajax({
            url: obj.ajaxurl,
            method: "POST",
            data: {
                action: "disconnect_psql_key"
            },
            success: function()
            {
                bt.val(pre_val).attr('disabled',false);
                window.location.reload();
            },
            error: function()
            {
                bt.val(pre_val).attr('disabled',false);
                alert('Something went wrong!');
            }
        });
    }

    $(document).on('click','#postsquirrel_selectable_box .psl_meta_nt_box',function(){

        if($(this).hasClass('selected'))
        {
            $(this).removeClass('selected');
        }
        else
        {
            $(this).addClass('selected');
        }

        syncDirectPostProfiles();

    });

    $(document).on('click','#psl_select_all_networks',function(){

        if($(this).is(":checked"))
        {
            $('.psl_meta_nt_box').addClass('selected');
        }
        else
        {
            $('.psl_meta_nt_box').removeClass('selected');
        }

        syncDirectPostProfiles();
    });

    $(document).on('click','#psl_disconnect_key',function(){

        if (confirm('Are you sure you want to disconnect the site from postsquirrel account?'))
        {
            removeSiteData();
        }
    });
});