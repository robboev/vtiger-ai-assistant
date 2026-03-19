{* AI Assistant Admin Settings Page *}

<div class="container-fluid ai-settings-page">
    <div class="widget_header row">
        <div class="col-lg-12">
            <h3>AI Assistant Settings</h3>
        </div>
    </div>

    {* Tabs *}
    <div class="row" style="margin-top: 15px;">
        <div class="col-lg-12">
            <ul class="nav nav-tabs">
                <li class="{if $TAB eq 'config'}active{/if}">
                    <a href="index.php?module=AIAssistant&view=Settings&tab=config">Configuration</a>
                </li>
                <li class="{if $TAB eq 'actions'}active{/if}">
                    <a href="index.php?module=AIAssistant&view=Settings&tab=actions">Actions</a>
                </li>
                <li class="{if $TAB eq 'queue'}active{/if}">
                    <a href="index.php?module=AIAssistant&view=Settings&tab=queue">Agent Queue</a>
                </li>
                <li class="{if $TAB eq 'audit'}active{/if}">
                    <a href="index.php?module=AIAssistant&view=Settings&tab=audit">Audit Log</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content" style="margin-top: 20px;">

        {* CONFIG TAB *}
        {if $TAB eq 'config'}
        <div class="tab-pane active">
            <form method="POST" action="index.php?module=AIAssistant&action=SettingsSave&operation=save_config&tab=config">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>LLM Provider Configuration</strong></div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label>Provider</label>
                                    <select name="provider" id="ai-provider-select" class="form-control">
                                        <option value="anthropic" {if $CURRENT_PROVIDER eq 'anthropic'}selected{/if}>
                                            Anthropic (Claude) — best tool_use support
                                        </option>
                                        <option value="openai" {if $CURRENT_PROVIDER eq 'openai'}selected{/if}>
                                            OpenAI (GPT) — widely available
                                        </option>
                                        <option value="ollama" {if $CURRENT_PROVIDER eq 'ollama'}selected{/if}>
                                            Ollama (Local) — free, runs on your server
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group" id="ai-apikey-group">
                                    <label>API Key</label>
                                    <input type="password" name="api_key" class="form-control"
                                           placeholder="{if $API_KEY_SET}(key is set — leave blank to keep){else}Enter API key{/if}">
                                    <small class="text-muted" id="ai-apikey-help">
                                        {if $API_KEY_SET}
                                            API key is configured. Leave blank to keep current key.
                                        {else}
                                            Required for Anthropic and OpenAI providers.
                                        {/if}
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>Model</label>
                                    <select name="model" id="ai-model-select" class="form-control">
                                        <optgroup label="Anthropic (Claude)" class="ai-models-anthropic">
                                            <option value="claude-haiku-4-5-20251001" {if $CURRENT_MODEL eq 'claude-haiku-4-5-20251001'}selected{/if}>
                                                Haiku 4.5 — fastest, ~$0.001/request
                                            </option>
                                            <option value="claude-sonnet-4-6" {if $CURRENT_MODEL eq 'claude-sonnet-4-6'}selected{/if}>
                                                Sonnet 4.6 — balanced, ~$0.01/request
                                            </option>
                                            <option value="claude-opus-4-6" {if $CURRENT_MODEL eq 'claude-opus-4-6'}selected{/if}>
                                                Opus 4.6 — most capable, ~$0.05/request
                                            </option>
                                        </optgroup>
                                        <optgroup label="OpenAI (GPT)" class="ai-models-openai">
                                            <option value="gpt-4o-mini" {if $CURRENT_MODEL eq 'gpt-4o-mini'}selected{/if}>
                                                GPT-4o Mini — cheapest, ~$0.001/request
                                            </option>
                                            <option value="gpt-4o" {if $CURRENT_MODEL eq 'gpt-4o'}selected{/if}>
                                                GPT-4o — balanced, ~$0.01/request
                                            </option>
                                        </optgroup>
                                        <optgroup label="Ollama (Local/Free)" class="ai-models-ollama">
                                            <option value="llama3" {if $CURRENT_MODEL eq 'llama3'}selected{/if}>
                                                Llama 3 — good general purpose
                                            </option>
                                            <option value="mistral" {if $CURRENT_MODEL eq 'mistral'}selected{/if}>
                                                Mistral — fast, efficient
                                            </option>
                                            <option value="mixtral" {if $CURRENT_MODEL eq 'mixtral'}selected{/if}>
                                                Mixtral — best local quality
                                            </option>
                                        </optgroup>
                                    </select>
                                </div>

                                <div class="form-group" id="ai-baseurl-group">
                                    <label>API Base URL <small class="text-muted">(optional, auto-detected per provider)</small></label>
                                    <input type="text" name="api_base_url" class="form-control"
                                           value="{$API_BASE_URL}"
                                           placeholder="Leave blank for default">
                                </div>

                                <div class="form-group">
                                    <label>Rate Limit (per user per hour)</label>
                                    <input type="number" name="rate_limit" class="form-control"
                                           value="{$RATE_LIMIT}" min="1" max="1000">
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="enabled" value="1" checked>
                                        Enable AI Assistant
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary">Save Configuration</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>Status</strong></div>
                            <div class="panel-body">
                                <table class="table table-condensed">
                                    <tr>
                                        <td>Provider</td>
                                        <td><span class="label label-default">{$CURRENT_PROVIDER}</span></td>
                                    </tr>
                                    <tr>
                                        <td>Model</td>
                                        <td><span class="label label-default">{$CURRENT_MODEL}</span></td>
                                    </tr>
                                    <tr>
                                        <td>API Key</td>
                                        <td>
                                            {if $CURRENT_PROVIDER eq 'ollama'}
                                                <span class="label label-success">Not needed (local)</span>
                                            {elseif $API_KEY_SET}
                                                <span class="label label-success">Configured</span>
                                            {else}
                                                <span class="label label-danger">Not Set</span>
                                            {/if}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Built-in Actions</td>
                                        <td><span class="label label-info">{count($BUILTIN_ACTIONS)}</span></td>
                                    </tr>
                                    <tr>
                                        <td>Generated Actions</td>
                                        <td><span class="label label-info">{count($ACTIONS)}</span></td>
                                    </tr>
                                </table>

                                <div class="alert alert-info" style="margin-top:15px; font-size:12px;">
                                    <strong>Cost Guide:</strong><br>
                                    Ollama = free (needs server with 8GB+ RAM)<br>
                                    GPT-4o Mini / Haiku = ~$0.001/request (~$1/1000 chats)<br>
                                    Sonnet / GPT-4o = ~$0.01/request (~$10/1000 chats)<br>
                                    Opus = ~$0.05/request (use for CLI agent only)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        {/if}

        {* ACTIONS TAB *}
        {if $TAB eq 'actions'}
        <div class="tab-pane active">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Built-in Actions</strong>
                    <small class="text-muted">(always active)</small>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$BUILTIN_ACTIONS item=ACTION}
                            <tr>
                                <td><code>{$ACTION}</code></td>
                                <td><span class="label label-success">active</span></td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Generated Actions</strong>
                    <small class="text-muted">(built by CLI agent)</small>
                </div>
                <div class="panel-body">
                    {if count($ACTIONS) eq 0}
                        <p class="text-muted">No generated actions yet. Actions are created automatically when users request capabilities that don't exist.</p>
                    {else}
                    <table class="table table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Generated By</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$ACTIONS item=ACTION}
                            <tr>
                                <td><code>{$ACTION.action_name}</code></td>
                                <td>{$ACTION.source}</td>
                                <td>
                                    {if $ACTION.status eq 'active'}
                                        <span class="label label-success">active</span>
                                    {elseif $ACTION.status eq 'pending_review'}
                                        <span class="label label-warning">pending review</span>
                                    {else}
                                        <span class="label label-danger">disabled</span>
                                    {/if}
                                </td>
                                <td>{$ACTION.generated_by}</td>
                                <td>{$ACTION.created_at}</td>
                                <td>
                                    {if $ACTION.status eq 'pending_review'}
                                        <a href="index.php?module=AIAssistant&action=SettingsSave&operation=approve_action&action_name={$ACTION.action_name}&tab=actions"
                                           class="btn btn-xs btn-success">Approve</a>
                                    {/if}
                                    {if $ACTION.status eq 'active'}
                                        <a href="index.php?module=AIAssistant&action=SettingsSave&operation=disable_action&action_name={$ACTION.action_name}&tab=actions"
                                           class="btn btn-xs btn-danger">Disable</a>
                                    {/if}
                                    {if $ACTION.status eq 'disabled'}
                                        <a href="index.php?module=AIAssistant&action=SettingsSave&operation=enable_action&action_name={$ACTION.action_name}&tab=actions"
                                           class="btn btn-xs btn-default">Enable</a>
                                    {/if}
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    {/if}
                </div>
            </div>
        </div>
        {/if}

        {* QUEUE TAB *}
        {if $TAB eq 'queue'}
        <div class="tab-pane active">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Agent Build Queue</strong>
                    <div class="pull-right">
                        <a href="index.php?module=AIAssistant&action=SettingsSave&operation=clear_queue&tab=queue"
                           class="btn btn-xs btn-default">Clear Completed/Failed</a>
                    </div>
                </div>
                <div class="panel-body">
                    {if count($QUEUE) eq 0}
                        <p class="text-muted">No items in queue.</p>
                    {else}
                    <table class="table table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Requested Action</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Result</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$QUEUE item=ITEM}
                            <tr>
                                <td>{$ITEM.id}</td>
                                <td><code>{$ITEM.requested_action}</code></td>
                                <td>{$ITEM.user_name}</td>
                                <td>
                                    {if $ITEM.status eq 'pending'}
                                        <span class="label label-info">pending</span>
                                    {elseif $ITEM.status eq 'processing'}
                                        <span class="label label-warning">processing</span>
                                    {elseif $ITEM.status eq 'completed'}
                                        <span class="label label-success">completed</span>
                                    {else}
                                        <span class="label label-danger">failed</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $ITEM.result_action}
                                        <code>{$ITEM.result_action}</code>
                                    {elseif $ITEM.error_message}
                                        <small class="text-danger">{$ITEM.error_message|truncate:80}</small>
                                    {/if}
                                </td>
                                <td>{$ITEM.created_at}</td>
                                <td>
                                    {if $ITEM.status eq 'failed'}
                                        <a href="index.php?module=AIAssistant&action=SettingsSave&operation=retry_queue&queue_id={$ITEM.id}&tab=queue"
                                           class="btn btn-xs btn-warning">Retry</a>
                                    {/if}
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    {/if}
                </div>
            </div>
        </div>
        {/if}

        {* AUDIT TAB *}
        {if $TAB eq 'audit'}
        <div class="tab-pane active">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Audit Log</strong> <small class="text-muted">(last 100 entries)</small></div>
                <div class="panel-body">
                    {if count($AUDIT_LOG) eq 0}
                        <p class="text-muted">No actions executed yet.</p>
                    {else}
                    <table class="table table-striped table-condensed" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Tenant</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$AUDIT_LOG item=LOG}
                            <tr>
                                <td>{$LOG.created_at}</td>
                                <td>{$LOG.user_name}</td>
                                <td>{$LOG.tenant_id}</td>
                                <td><code>{$LOG.action_name}</code></td>
                                <td>
                                    {if $LOG.status eq 'success'}
                                        <span class="label label-success">OK</span>
                                    {elseif $LOG.status eq 'rejected'}
                                        <span class="label label-warning">rejected</span>
                                    {else}
                                        <span class="label label-danger">error</span>
                                    {/if}
                                </td>
                                <td>{$LOG.execution_time_ms}ms</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    {/if}
                </div>
            </div>
        </div>
        {/if}

    </div>
</div>
