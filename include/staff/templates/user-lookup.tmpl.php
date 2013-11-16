<h3>User Lookup</h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form method="post" action="" onsubmit="javascript:
    var form=$(this), target=$('#client-info'), target_id=$('#user_id'),
        user_id=$(this.user_id).val();
    if (user_id) {
        target_id.val(user_id);
        target.text($('#user-lookup-name').text()
            + ' &lt;' + $('#user-lookup-email').text() + '&gt;');
    }
    $('#user-lookup').hide();
    $('#overlay').hide();
    return false;">
<div id="dialog-body">
<input type="text" style="width:100%" placeholder="Search" id="client-search"/>
<br/><br/>
<i class="icon-user icon-4x pull-left icon-border"></i>
<div><strong id="user-lookup-name"><?php echo $user_info['name']; ?></strong></div>
<div>&lt;<span id="user-lookup-email"><?php echo $user_info['email']; ?></span>&gt;</div>
<input type="hidden" id="user-lookup-id" name="user_id" value=""/>
<div class="clear"></div>
</div>
    <hr>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" value="Cancel" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Update">
        </span>
     </p>
</form>
<div class="clear"></div>
<script type="text/javascript">
$(function() {
    $('#client-search').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/users?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            $('#user-lookup-name').text(obj.name);
            $('#user-lookup-email').text(obj.email);
            $('#user-lookup-id').val(obj.id);
        },
        property: "/bin/true"
    });
});
</script>
