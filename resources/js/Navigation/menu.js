const menu = [
  {
    groupKey: 'navigation.operation',
    roles: ['admin'],
    items: [
      { labelKey: 'navigation.dashboard', routeName: 'dashboard', icon: 'fas fa-gauge-high', active: ['dashboard'] },
      { labelKey: 'navigation.point_of_sale', routeName: 'carts.index', icon: 'fas fa-cash-register', active: ['carts.*'], highlight: true },
      { labelKey: 'navigation.sales', routeName: 'sales.index', icon: 'fas fa-receipt', active: ['sales.*'] },
      { labelKey: 'navigation.cash_shifts', legacyLabelKey: 'navigation.cash_register', routeName: 'cash-registers.index', icon: 'fas fa-lock-open', active: ['cash-registers.*'] },
    ],
  },
  {
    groupKey: 'navigation.inventory',
    roles: ['admin'],
    items: [
      { labelKey: 'navigation.products', routeName: 'products.index', icon: 'fas fa-boxes', active: ['products.*'] },
      { labelKey: 'navigation.categories', routeName: 'categories.index', icon: 'fas fa-tags', active: ['categories.*'] },
      { labelKey: 'navigation.units', routeName: 'unit-types.index', icon: 'fas fa-balance-scale', active: ['unit-types.*'] },
      { labelKey: 'navigation.suppliers', routeName: 'suppliers.index', icon: 'fas fa-truck', active: ['suppliers.*'] },
    ],
  },
  {
    groupKey: 'navigation.management',
    roles: ['admin'],
    items: [
      { labelKey: 'navigation.employees', routeName: 'employees.index', icon: 'fas fa-id-badge', active: ['employees.*'] },
      { labelKey: 'navigation.reports', routeName: 'reports.index', icon: 'fas fa-chart-bar', active: ['reports.*'] },
      { labelKey: 'navigation.configuration', routeName: 'settings.edit', icon: 'fas fa-cog', active: ['settings.*'] },
    ],
  },
  {
    groupKey: 'navigation.operation',
    roles: ['cajero'],
    items: [
      { labelKey: 'navigation.dashboard', routeName: 'dashboard', icon: 'fas fa-house', active: ['dashboard'] },
      { labelKey: 'navigation.point_of_sale', routeName: 'carts.index', icon: 'fas fa-cash-register', active: ['carts.*'], highlight: true },
      { labelKey: 'navigation.my_cash_register', routeName: 'cash-registers.index', icon: 'fas fa-lock-open', active: ['cash-registers.*'] },
      { labelKey: 'navigation.my_sales', routeName: 'sales.index', icon: 'fas fa-receipt', active: ['sales.*'] },
      { labelKey: 'navigation.products', routeName: 'products.index', icon: 'fas fa-boxes', active: ['products.*'] },
    ],
  },
  {
    groupKey: 'navigation.account',
    roles: ['admin', 'cajero'],
    items: [
      { labelKey: 'navigation.profile', routeName: 'profile.edit', icon: 'fas fa-user-cog', active: ['profile.*'] },
    ],
  },
];

export function menuForRole(role, t = (key) => key) {
  return menu
    .filter((group) => group.roles.includes(role))
    .map((group) => ({
      ...group,
      group: t(group.groupKey),
      items: group.items.filter((item) => !item.roles || item.roles.includes(role)),
    }))
    .map((group) => ({
      ...group,
      items: group.items.map((item) => ({
        ...item,
        label: t(item.labelKey),
      })),
    }))
    .filter((group) => group.items.length);
}

export function currentRouteTitle(role, t = (key) => key) {
  const groups = menuForRole(role, t);
  const item = groups
    .flatMap((group) => group.items)
    .find((menuItem) => menuItem.active.some((pattern) => route().current(pattern)));

  return item?.label || 'LaraTory';
}
