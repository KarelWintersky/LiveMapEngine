<div class="header">
    <h2>Пользователи</h2>
</div>

<div class="content">
    <div style="float: right">
        <a class="pure-button pure-button-success" href="{Arris\AppRouter::getRouter('admin.users.view.create')}">Create user</a>
    </div>

    <table class="pure-table pure-table-bordered {*pure-table-striped*}" width="100%">
        <thead>
            <tr>
                <th colspan="6">

                </th>
            </tr>
            <tr>
                <th>E-Mail</th>
                <th>Username</th>
                <th>Role</th>
                <th>Registred</th>
                <th>Last login</th>
                <th>Actions</th>
            </tr>

        </thead>
        <tbody>
        {foreach $userlist as $user}
            <tr>
                <td>{$user.email}</td>
                <td>{$user.username}</td>
                <td>
                    {foreach $user.roles as $role}
                        <strong>{$role}</strong><br>
                    {/foreach}
                </td>
                <td>{$user.registered|convertDateTime}</td>
                <td>{$user.last_login|convertDateTime}</td>
                <td>
                    <a class="pure-button pure-button-success" href="{Arris\AppRouter::getRouter('admin.users.view.edit')}?id={$user.id}">Edit</a>
                    |
                    <a class="pure-button pure-button-error" href="{Arris\AppRouter::getRouter('admin.users.callback.delete')}?id={$user.id}">Delete</a>
                </td>
            </tr>
        {/foreach}
        </tbody>

    </table>
</div>