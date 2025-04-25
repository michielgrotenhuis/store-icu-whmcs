{if $status == 'Active' || $status == 'active'}
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> Your store is active and running.
</div>
{elseif $status == 'Suspended' || $status == 'suspended'}
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> Your store is currently suspended. Please contact support.
</div>
{elseif $status == 'Terminated' || $status == 'terminated'}
<div class="alert alert-danger">
    <i class="fas fa-times-circle"></i> Your store has been terminated.
</div>
{/if}

<div class="row">
    <div class="col-md-8">
        <h3>Store Information</h3>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4 text-right"><strong>Shop Handle:</strong></div>
                    <div class="col-md-8">{$shop_handle}</div>
                </div>
                <div class="row">
                    <div class="col-md-4 text-right"><strong>Status:</strong></div>
                    <div class="col-md-8">{$status}</div>
                </div>
                <div class="row">
                    <div class="col-md-4 text-right"><strong>Created:</strong></div>
                    <div class="col-md-8">{$created_at}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <h3>Quick Links</h3>
        <div class="panel panel-default">
            <div class="panel-body">
                <form method="post" action="clientarea.php?action=productdetails">
                    <input type="hidden" name="id" value="{$serviceid}" />
                    <input type="hidden" name="modop" value="custom" />
                    <input type="hidden" name="a" value="sso" />
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Login to Store
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3>Store Statistics</h3>
        <div class="panel panel-default">
            <div class="panel-body">
                <p>Store statistics will be displayed here in future updates.</p>
            </div>
        </div>
    </div>
</div>
