@php
    use App\Support\AdminPermissions;
    $defs = AdminPermissions::definitions();
    $grouped = collect($defs)
        ->mapWithKeys(fn($label, $key) => [$key => ['label' => $label, 'group' => explode('.', $key)[0]]])
        ->groupBy('group');
@endphp

<h2>Permissions & Roles</h2>

<h3>Source of truth</h3>
<ul>
    <li><strong>Definitions:</strong> <code>app/Support/AdminPermissions.php</code> — flat list of slugs.</li>
    <li><strong>Sidebar visibility:</strong> <code>app/Helpers/AdminNavigation.php</code> per item
        <code>permission</code> key.</li>
    <li><strong>Route guard:</strong> <code>->middleware('admin.permission:slug1,slug2')</code> in
        <code>routes/central.php</code>.</li>
    <li><strong>Storage:</strong> central <code>admin_roles</code> table stores the permission slugs as a JSON column on
        the role.</li>
    <li><strong>Resolver:</strong> <code>App\Models\AdminUser::hasPermission(string)</code> (Super Admin role implicitly
        has every permission).</li>
</ul>

<h3>All permissions</h3>
@foreach ($grouped as $group => $items)
    <h3>{{ ucfirst($group) }}</h3>
    <table>
        <tr>
            <th>Slug</th>
            <th>Description</th>
        </tr>
        @foreach ($items as $key => $meta)
            <tr>
                <td><code>{{ $key }}</code></td>
                <td>{{ $meta['label'] }}</td>
            </tr>
        @endforeach
    </table>
@endforeach

<h3>How to add a new permission</h3>
<ol>
    <li>Add the slug to <code>AdminPermissions::definitions()</code>.</li>
    <li>Reference it on the corresponding admin route via <code>->middleware('admin.permission:my.slug')</code>.</li>
    <li>Reference it on the matching nav item in <code>AdminNavigation::sections()</code>.</li>
    <li>Optionally update <code>legacyMap()</code> if older role rows used a different name.</li>
    <li>Assign it to roles via <strong>Admins → Role & Permissions</strong>.</li>
</ol>