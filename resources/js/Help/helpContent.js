export const helpManualSections = [
  {
    icon: 'fas fa-cash-register',
    color: 'primary',
    titleKey: 'help.manual.pos.title',
    bodyKey: 'help.manual.pos.body',
    bullets: [
      'help.manual.pos.step_search',
      'help.manual.pos.step_cart',
      'help.manual.pos.step_pay',
      'help.manual.pos.step_document',
    ],
  },
  {
    icon: 'fas fa-lock-open',
    color: 'success',
    titleKey: 'help.manual.cash.title',
    bodyKey: 'help.manual.cash.body',
    bullets: [
      'help.manual.cash.step_open',
      'help.manual.cash.step_monitor',
      'help.manual.cash.step_close',
    ],
  },
  {
    icon: 'fas fa-boxes',
    color: 'info',
    titleKey: 'help.manual.inventory.title',
    bodyKey: 'help.manual.inventory.body',
    bullets: [
      'help.manual.inventory.step_create',
      'help.manual.inventory.step_barcode',
      'help.manual.inventory.step_stock',
    ],
  },
  {
    icon: 'fas fa-chart-line',
    color: 'warning',
    titleKey: 'help.manual.reports.title',
    bodyKey: 'help.manual.reports.body',
    bullets: [
      'help.manual.reports.step_filter',
      'help.manual.reports.step_compare',
      'help.manual.reports.step_export',
    ],
  },
];

export const helpFaqItems = [
  {
    icon: 'fas fa-barcode',
    questionKey: 'help.faq.scanner.question',
    answerKey: 'help.faq.scanner.answer',
  },
  {
    icon: 'fas fa-wallet',
    questionKey: 'help.faq.payments.question',
    answerKey: 'help.faq.payments.answer',
  },
  {
    icon: 'fas fa-receipt',
    questionKey: 'help.faq.documents.question',
    answerKey: 'help.faq.documents.answer',
  },
  {
    icon: 'fas fa-user-shield',
    questionKey: 'help.faq.roles.question',
    answerKey: 'help.faq.roles.answer',
  },
  {
    icon: 'fas fa-box-open',
    questionKey: 'help.faq.stock.question',
    answerKey: 'help.faq.stock.answer',
  },
];

export const guidedTours = {
  dashboard: [
    {
      selector: '[data-tour="page-header"]',
      icon: 'fas fa-tachometer-alt',
      titleKey: 'help.tours.dashboard.header.title',
      bodyKey: 'help.tours.dashboard.header.body',
    },
    {
      selector: '[data-tour="dashboard-hero"]',
      icon: 'fas fa-chart-line',
      titleKey: 'help.tours.dashboard.hero.title',
      bodyKey: 'help.tours.dashboard.hero.body',
    },
    {
      selector: '[data-tour="summary-cards"]',
      icon: 'fas fa-gauge-high',
      titleKey: 'help.tours.dashboard.summary.title',
      bodyKey: 'help.tours.dashboard.summary.body',
    },
    {
      selector: '[data-tour="dashboard-alerts"]',
      fallbackSelector: '[data-tour="summary-cards"]',
      icon: 'fas fa-triangle-exclamation',
      titleKey: 'help.tours.dashboard.alerts.title',
      bodyKey: 'help.tours.dashboard.alerts.body',
    },
    {
      selector: '[data-tour="quick-access"]',
      fallbackSelector: '[data-tour="topbar-command"]',
      icon: 'fas fa-bolt',
      titleKey: 'help.tours.dashboard.quick.title',
      bodyKey: 'help.tours.dashboard.quick.body',
    },
  ],
  pos: [
    {
      selector: '[data-tour="pos-register"]',
      icon: 'fas fa-lock-open',
      titleKey: 'help.tours.pos.register.title',
      bodyKey: 'help.tours.pos.register.body',
    },
    {
      selector: '[data-tour="pos-search"]',
      icon: 'fas fa-barcode',
      titleKey: 'help.tours.pos.search.title',
      bodyKey: 'help.tours.pos.search.body',
    },
    {
      selector: '.pos-product-card',
      fallbackSelector: '[data-tour="pos-products"]',
      icon: 'fas fa-box',
      titleKey: 'help.tours.pos.product_card.title',
      bodyKey: 'help.tours.pos.product_card.body',
    },
    {
      selector: '[data-tour="pos-cart"]',
      icon: 'fas fa-shopping-basket',
      titleKey: 'help.tours.pos.cart.title',
      bodyKey: 'help.tours.pos.cart.body',
    },
    {
      selector: '.pos-cart-item',
      fallbackSelector: '[data-tour="pos-cart"]',
      icon: 'fas fa-plus-minus',
      titleKey: 'help.tours.pos.cart_item.title',
      bodyKey: 'help.tours.pos.cart_item.body',
    },
    {
      selector: '[data-tour="pos-pay"]',
      icon: 'fas fa-credit-card',
      titleKey: 'help.tours.pos.pay.title',
      bodyKey: 'help.tours.pos.pay.body',
    },
  ],
  products: [
    {
      selector: '[data-tour="page-header"]',
      icon: 'fas fa-boxes',
      titleKey: 'help.tours.products.workflow.title',
      bodyKey: 'help.tours.products.workflow.body',
    },
    {
      selector: '[data-tour="create-button"]',
      fallbackSelector: '[data-tour="primary-action"]',
      icon: 'fas fa-plus',
      titleKey: 'help.tours.products.create.title',
      bodyKey: 'help.tours.products.create.body',
    },
    {
      selector: '[data-tour="product-form-identity"]',
      fallbackSelector: '[data-tour="create-button"]',
      action: { type: 'click', selector: '[data-tour="create-button"]' },
      icon: 'fas fa-barcode',
      titleKey: 'help.tours.products.form_identity.title',
      bodyKey: 'help.tours.products.form_identity.body',
    },
    {
      selector: '[data-tour="product-form-classification"]',
      fallbackSelector: '[data-tour="product-form-identity"]',
      icon: 'fas fa-layer-group',
      titleKey: 'help.tours.products.form_classification.title',
      bodyKey: 'help.tours.products.form_classification.body',
    },
    {
      selector: '[data-tour="product-form-stock-price"]',
      fallbackSelector: '[data-tour="product-form-classification"]',
      icon: 'fas fa-coins',
      titleKey: 'help.tours.products.form_stock_price.title',
      bodyKey: 'help.tours.products.form_stock_price.body',
    },
    {
      selector: '[data-tour="product-form-save"]',
      fallbackSelector: '[data-tour="product-form-stock-price"]',
      icon: 'fas fa-save',
      titleKey: 'help.tours.products.form_save.title',
      bodyKey: 'help.tours.products.form_save.body',
    },
  ],
  cash: [
    {
      selector: '[data-tour="page-header"]',
      icon: 'fas fa-lock-open',
      titleKey: 'help.tours.cash.header.title',
      bodyKey: 'help.tours.cash.header.body',
    },
    {
      selector: '[data-tour="records-list"]',
      fallbackSelector: '[data-tour="cash-status"]',
      icon: 'fas fa-users',
      titleKey: 'help.tours.cash.employee_board.title',
      bodyKey: 'help.tours.cash.employee_board.body',
    },
    {
      selector: '[data-tour="cash-status"]',
      icon: 'fas fa-stopwatch',
      titleKey: 'help.tours.cash.status.title',
      bodyKey: 'help.tours.cash.status.body',
    },
    {
      selector: '[data-tour="row-actions"]',
      fallbackSelector: '[data-tour="records-list"]',
      icon: 'fas fa-lock',
      titleKey: 'help.tours.cash.actions.title',
      bodyKey: 'help.tours.cash.actions.body',
    },
  ],
  reports: [
    {
      selector: '[data-tour="reports-filters"]',
      icon: 'fas fa-filter',
      titleKey: 'help.tours.reports.filters.title',
      bodyKey: 'help.tours.reports.filters.body',
    },
    {
      selector: '[data-tour="summary-cards"]',
      icon: 'fas fa-calculator',
      titleKey: 'help.tours.reports.summary.title',
      bodyKey: 'help.tours.reports.summary.body',
    },
    {
      selector: '[data-tour="reports-analytics"]',
      icon: 'fas fa-chart-simple',
      titleKey: 'help.tours.reports.analytics.title',
      bodyKey: 'help.tours.reports.analytics.body',
    },
  ],
  sales: [
    {
      selector: '[data-tour="create-button"]',
      fallbackSelector: '[data-tour="primary-action"]',
      icon: 'fas fa-cash-register',
      titleKey: 'help.tours.sales.new_sale.title',
      bodyKey: 'help.tours.sales.new_sale.body',
    },
    {
      selector: '[data-tour="filters-panel"]',
      icon: 'fas fa-filter',
      titleKey: 'help.tours.sales.filters.title',
      bodyKey: 'help.tours.sales.filters.body',
    },
    {
      selector: '[data-tour="records-list"]',
      icon: 'fas fa-table-list',
      titleKey: 'help.tours.sales.table.title',
      bodyKey: 'help.tours.sales.table.body',
    },
    {
      selector: '[data-tour="row-actions"]',
      fallbackSelector: '[data-tour="records-list"]',
      icon: 'fas fa-ellipsis',
      titleKey: 'help.tours.sales.actions.title',
      bodyKey: 'help.tours.sales.actions.body',
    },
  ],
  employees: [
    {
      selector: '[data-tour="create-button"]',
      fallbackSelector: '[data-tour="primary-action"]',
      icon: 'fas fa-user-plus',
      titleKey: 'help.tours.employees.create.title',
      bodyKey: 'help.tours.employees.create.body',
    },
    {
      selector: '[data-tour="filters-panel"]',
      icon: 'fas fa-filter',
      titleKey: 'help.tours.employees.filters.title',
      bodyKey: 'help.tours.employees.filters.body',
    },
    {
      selector: '[data-tour="records-list"]',
      icon: 'fas fa-users',
      titleKey: 'help.tours.employees.table.title',
      bodyKey: 'help.tours.employees.table.body',
    },
    {
      selector: '[data-tour="row-actions"]',
      fallbackSelector: '[data-tour="records-list"]',
      icon: 'fas fa-user-gear',
      titleKey: 'help.tours.employees.actions.title',
      bodyKey: 'help.tours.employees.actions.body',
    },
  ],
  catalog: [
    {
      selector: '[data-tour="create-button"]',
      fallbackSelector: '[data-tour="primary-action"]',
      icon: 'fas fa-plus',
      titleKey: 'help.tours.catalog.create.title',
      bodyKey: 'help.tours.catalog.create.body',
    },
    {
      selector: '[data-tour="filters-panel"]',
      icon: 'fas fa-filter',
      titleKey: 'help.tours.catalog.filters.title',
      bodyKey: 'help.tours.catalog.filters.body',
    },
    {
      selector: '[data-tour="records-list"]',
      icon: 'fas fa-table-list',
      titleKey: 'help.tours.catalog.table.title',
      bodyKey: 'help.tours.catalog.table.body',
    },
    {
      selector: '[data-tour="row-actions"]',
      fallbackSelector: '[data-tour="records-list"]',
      icon: 'fas fa-pen-to-square',
      titleKey: 'help.tours.catalog.actions.title',
      bodyKey: 'help.tours.catalog.actions.body',
    },
  ],
  settings: [
    {
      selector: '[data-tour="settings-business"]',
      icon: 'fas fa-store',
      titleKey: 'help.tours.settings.business.title',
      bodyKey: 'help.tours.settings.business.body',
    },
    {
      selector: '[data-tour="settings-format"]',
      icon: 'fas fa-globe',
      titleKey: 'help.tours.settings.format.title',
      bodyKey: 'help.tours.settings.format.body',
    },
    {
      selector: '[data-tour="settings-printing"]',
      icon: 'fas fa-print',
      titleKey: 'help.tours.settings.printing.title',
      bodyKey: 'help.tours.settings.printing.body',
    },
    {
      selector: '[data-tour="settings-save"]',
      icon: 'fas fa-save',
      titleKey: 'help.tours.settings.save.title',
      bodyKey: 'help.tours.settings.save.body',
    },
  ],
  profile: [
    {
      selector: '[data-tour="appearance-panel"]',
      icon: 'fas fa-palette',
      titleKey: 'help.tours.profile.appearance.title',
      bodyKey: 'help.tours.profile.appearance.body',
    },
    {
      selector: '[data-tour="profile-account"]',
      icon: 'fas fa-user-cog',
      titleKey: 'help.tours.profile.account.title',
      bodyKey: 'help.tours.profile.account.body',
    },
    {
      selector: '[data-tour="profile-card"]',
      icon: 'fas fa-id-card',
      titleKey: 'help.tours.profile.card.title',
      bodyKey: 'help.tours.profile.card.body',
    },
  ],
  default: [
    {
      selector: '[data-tour="page-header"]',
      icon: 'fas fa-compass',
      titleKey: 'help.tours.default.header.title',
      bodyKey: 'help.tours.default.header.body',
    },
    {
      selector: '[data-tour="topbar-command"]',
      icon: 'fas fa-terminal',
      titleKey: 'help.tours.global.commands.title',
      bodyKey: 'help.tours.global.commands.body',
    },
  ],
};

export function tourKeyForCurrentRoute() {
  if (route().current('carts.*')) return 'pos';
  if (route().current('dashboard')) return 'dashboard';
  if (route().current('products.*')) return 'products';
  if (route().current('cash-registers.*')) return 'cash';
  if (route().current('reports.*')) return 'reports';
  if (route().current('sales.*')) return 'sales';
  if (route().current('employees.*')) return 'employees';
  if (route().current('categories.*') || route().current('unit-types.*') || route().current('suppliers.*')) return 'catalog';
  if (route().current('settings.*')) return 'settings';
  if (route().current('profile.*')) return 'profile';

  return 'default';
}
