Dashboard Sidebar Component

resources/views/components/sidebar.blade.php owns the sidebar menu data and renders it with App\Support\AdminSidebarMenu::render($adminSidebar).

AdminSidebarMenu is an HTML renderer only. To add or change menu entries, edit the data array in sidebar.blade.php; do not add menu items inside AdminSidebarMenu.

Each group supports label, visible, and items. Each item supports label, icon, route, params, url, active, badge, children, and visible. Use route plus optional params, or url for placeholder/external links. Keep icon as a sprite symbol id without the leading #.
