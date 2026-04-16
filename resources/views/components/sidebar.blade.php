<template>
    <div class="sidebar">
        <ul class="menu">
            <li class="menu-item">
                <a href="/dashboard">
                    <i class="icon-dashboard"></i> Dashboard
                </a>
            </li>
            <li class="menu-item">
                <a href="/products">
                    <i class="icon-products"></i> Products
                </a>
            </li>
            <li class="menu-item">
                <a href="/sales">
                    <i class="icon-sales"></i> Sales
                </a>
            </li>
            <li class="menu-item">
                <a href="/inventory">
                    <i class="icon-inventory"></i> Inventory
                </a>
            </li>
            <li class="menu-item">
                <a href="/reports">
                    <i class="icon-reports"></i> Reports
                </a>
            </li>
            <li class="menu-item">
                <a href="/settings">
                    <i class="icon-settings"></i> Settings
                </a>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'Sidebar',
};
</script>

<style scoped>
.sidebar {
    width: 250px;
    background-color: #34495e;
    color: white;
    height: 100vh;
}

.menu {
    list-style-type: none;
    padding: 0;
}

.menu-item {
    padding: 15px;
}

.menu-item a {
    color: white;
    text-decoration: none;
}

.menu-item a:hover {
    text-decoration: underline;
}
</style>