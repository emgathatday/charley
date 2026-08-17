Dashboard UI Components

Dashboard shared UI uses Blade components under resources/views/components/admin.

Data stays in the page view. Components only render markup from props/data passed by the view.

Examples:

<x-admin.sidebar-menu :items="$adminSidebar" />
<x-admin.stat-cards :items="$statCards" />
<x-admin.tab-bar :items="$tabBar" />
<x-admin.input label="First name" name="first_name" :value="old('first_name')" />
<x-admin.icon name="billing" />
