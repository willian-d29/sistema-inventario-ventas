export default {
  es: {
    help: {
      button: 'Ayuda y tutorial',
      title: 'Centro de ayuda',
      subtitle: 'Aprende LaraTory con guías rápidas, respuestas frecuentes y recorridos visuales.',
      open_manual: 'Manual',
      open_faq: 'Preguntas',
      open_tour: 'Guía',
      start_tour: 'Iniciar guía de esta pantalla',
      restart_tour: 'Repetir guía',
      no_tour: 'Esta pantalla usa la guía general del sistema.',
      step_counter: '{current} de {total}',
      next: 'Siguiente',
      previous: 'Anterior',
      finish: 'Finalizar',
      skip: 'Saltar',
      missing_target: 'Este elemento no está visible ahora. Puedes continuar con el siguiente paso.',
      tip_title: 'Consejo rápido',
      tip_body: 'Usa el botón de ayuda cuando cambies de pantalla para ver una explicación específica.',
      tabs: {
        manual: 'Manual',
        faq: 'FAQ',
        tour: 'Guía de uso',
      },
      manual: {
        pos: {
          title: 'Punto de venta',
          body: 'Flujo recomendado para vender rápido en tienda física.',
          step_search: 'Busca por nombre o escanea el código de barras con el lector externo.',
          step_cart: 'Agrega productos a la cesta y ajusta cantidades antes de cobrar.',
          step_pay: 'Cobra con efectivo, Yape, Plin, tarjeta, transferencia o pago mixto.',
          step_document: 'Elige boleta o factura antes de confirmar la venta.',
        },
        cash: {
          title: 'Caja',
          body: 'La caja controla el efectivo del turno y separa pagos digitales.',
          step_open: 'Abre caja al iniciar atención con el efectivo inicial del cajón.',
          step_monitor: 'Revisa ventas, ingresos y egresos durante el turno.',
          step_close: 'Al cerrar, cuenta el efectivo físico y compara con el esperado.',
        },
        inventory: {
          title: 'Inventario',
          body: 'Registra productos claros para que el POS sea rápido y confiable.',
          step_create: 'Crea productos con nombre, categoría, unidad, precio y stock.',
          step_barcode: 'Escanea el código para guardarlo sin escribirlo manualmente.',
          step_stock: 'Mantén costos y stock actualizados para reportes correctos.',
        },
        reports: {
          title: 'Reportes',
          body: 'Usa reportes para entender ventas, métodos de pago y utilidad.',
          step_filter: 'Filtra por fechas para revisar periodos concretos.',
          step_compare: 'Compara productos, métodos de pago y desempeño de cajeros.',
          step_export: 'Descarga PDF o Excel cuando necesites respaldos.',
        },
      },
      faq: {
        scanner: {
          question: '¿Cómo uso un lector de código de barras?',
          answer: 'Mantén la ventana de LaraTory activa. El lector escribe el código como teclado; al terminar con Enter, el POS busca y agrega el producto.',
        },
        payments: {
          question: '¿Puedo cobrar con varios métodos?',
          answer: 'Sí. En el modal de pago usa pago mixto y reparte el total entre efectivo, Yape, Plin, tarjeta o transferencia.',
        },
        documents: {
          question: '¿Cuándo uso boleta o factura?',
          answer: 'Usa boleta para ventas simples a consumidor final y factura cuando el cliente necesita sustento tributario con RUC.',
        },
        roles: {
          question: '¿Qué diferencia hay entre administrador y cajero?',
          answer: 'El cajero vende y controla su caja. El administrador gestiona inventario, empleados, cajas, reportes y configuración.',
        },
        stock: {
          question: '¿Qué pasa si un producto no tiene stock?',
          answer: 'El POS lo muestra como no disponible para evitar ventas sin inventario. Actualiza stock desde productos.',
        },
      },
      tours: {
        global: {
          commands: {
            title: 'Comandos rápidos',
            body: 'Busca cualquier sección del sistema con este comando. También puedes usar la tecla /.',
          },
          notifications: {
            title: 'Avisos importantes',
            body: 'Aquí aparecen alertas de caja, stock bajo y acciones que necesitan atención.',
          },
          help: {
            title: 'Ayuda siempre disponible',
            body: 'Desde este botón puedes repetir el tour de la pantalla, leer el manual o resolver dudas frecuentes.',
          },
        },
        dashboard: {
          header: {
            title: 'Resumen del negocio',
            body: 'El panel muestra ventas, caja, productos críticos y actividad reciente para tomar decisiones rápidas.',
          },
          period: {
            title: 'Periodo de análisis',
            body: 'Cambia el mes para recalcular métricas, gráficos y comparaciones del panel.',
          },
          hero: {
            title: 'Resultado del día',
            body: 'Este bloque resume ingresos, utilidad y costo del día con accesos rápidos a reportes y venta.',
          },
          summary: {
            title: 'Indicadores principales',
            body: 'Estas tarjetas muestran señales rápidas: ventas, utilidad, productos activos y cajas abiertas.',
          },
          alerts: {
            title: 'Alertas operativas',
            body: 'Si hay diferencias de caja, stock bajo o productos sin costo, aparecerán aquí para actuar rápido.',
          },
          analytics: {
            title: 'Análisis visual',
            body: 'Estos gráficos separan métodos de pago, productos más vendidos y productos con mayor utilidad.',
          },
          trend: {
            title: 'Tendencia mensual',
            body: 'Compara el periodo actual con el año anterior y observa el volumen de ventas por mes.',
          },
          recent: {
            title: 'Actividad reciente',
            body: 'Aquí puedes revisar ventas recientes sin entrar todavía al historial completo.',
          },
          quick: {
            title: 'Accesos rápidos',
            body: 'Usa estos accesos para iniciar una venta, revisar cajas, registrar productos o abrir reportes.',
          },
        },
        pos: {
          search: {
            title: 'Buscar o escanear',
            body: 'Escribe el nombre del producto o escanea el código de barras. Si el producto existe, se agrega o aparece en resultados.',
          },
          shortcuts: {
            title: 'Atajos durante la venta',
            body: 'El POS acepta comandos de teclado para buscar, cobrar y cerrar modales sin soltar el flujo de caja.',
          },
          register: {
            title: 'Estado de caja',
            body: 'Antes de cobrar debe existir una caja abierta. El temporizador muestra cuánto tiempo lleva el turno activo.',
          },
          products: {
            title: 'Productos disponibles',
            body: 'Cada tarjeta muestra foto, stock, precio y código. Solo esta zona debería desplazarse cuando hay muchos productos.',
          },
          product_card: {
            title: 'Tarjeta de producto',
            body: 'Presiona una tarjeta para agregar el producto a la cesta. Si no hay stock, el sistema evita venderlo.',
          },
          cart: {
            title: 'Cesta de venta',
            body: 'Aquí ajustas cantidades, retiras productos y verificas el resumen antes de cobrar.',
          },
          cart_item: {
            title: 'Cantidad y retiro',
            body: 'Cuando hay productos en la cesta, puedes aumentar, reducir o retirar cada línea antes del pago.',
          },
          pay: {
            title: 'Cobrar venta',
            body: 'Este botón abre el modal de pago para elegir documento y método de pago.',
          },
        },
        products: {
          header: {
            title: 'Gestión de productos',
            body: 'Desde aquí registras productos, códigos de barras, precios, costos e imágenes.',
          },
          workflow: {
            title: 'Guía para crear un producto',
            body: 'Este recorrido se enfoca en el flujo real: abrir el modal, identificar el producto, clasificarlo, definir precio/stock y guardar.',
          },
          create: {
            title: 'Abrir registro',
            body: 'Usa este botón para abrir el modal de registro sin salir de la pantalla.',
          },
          form_identity: {
            title: 'Identificar el producto',
            body: 'Empieza por código de barras y nombre. Si escaneas un código, LaraTory lo conserva y puede completar datos cuando encuentre coincidencias.',
          },
          form_classification: {
            title: 'Ordenarlo para encontrarlo',
            body: 'Selecciona categoría y unidad. El proveedor es opcional, útil cuando quieres recordar dónde se compra.',
          },
          form_stock_price: {
            title: 'Precio, costo y stock',
            body: 'Completa costo, precio de venta y cantidad. Estos datos alimentan el POS, el stock y los reportes de utilidad.',
          },
          form_save: {
            title: 'Guardar y vender',
            body: 'Al guardar, el producto queda disponible para buscarlo o escanearlo en el punto de venta.',
          },
          summary: {
            title: 'Estado del inventario',
            body: 'Estas tarjetas muestran total, disponibles, stock bajo, agotados y productos sin costo.',
          },
          filters: {
            title: 'Filtros de productos',
            body: 'Filtra por nombre, código de barras, categoría, stock o estado para encontrar rápido un producto.',
          },
          table: {
            title: 'Listado de productos',
            body: 'La tabla concentra foto, código, categoría, stock, costo, precio y estado operativo.',
          },
          actions: {
            title: 'Editar o eliminar',
            body: 'Los botones de cada fila permiten corregir datos o eliminar productos cuando corresponda.',
          },
        },
        cash: {
          header: {
            title: 'Estado de cajas',
            body: 'Controla cajas abiertas, cierres y diferencias de efectivo por empleado.',
          },
          summary: {
            title: 'Resumen de cajas',
            body: 'Estas tarjetas muestran cuántas cajas están abiertas, cerradas y cuánto efectivo se espera.',
          },
          filters: {
            title: 'Filtro por empleado',
            body: 'Como administrador puedes filtrar por cajero o estado para ubicar una caja específica.',
          },
          employee_board: {
            title: 'Tablero de empleados',
            body: 'Cada tarjeta muestra el estado de caja del empleado, ventas, efectivo esperado y tiempo activo.',
          },
          status: {
            title: 'Lectura rápida de caja',
            body: 'Los colores ayudan a diferenciar caja abierta, cerrada o con diferencia.',
          },
          actions: {
            title: 'Cerrar cajas',
            body: 'Si eres administrador puedes cerrar cajas abiertas de empleados y dejar el cierre auditado.',
          },
        },
        reports: {
          header: {
            title: 'Reportes',
            body: 'Revisa ventas, utilidad, pagos y rankings sin mezclar datos operativos.',
          },
          filters: {
            title: 'Filtros de periodo',
            body: 'Cambia fechas para actualizar los gráficos y exportaciones.',
          },
          summary: {
            title: 'Totales del reporte',
            body: 'Estas tarjetas resumen ingresos, costos, utilidad, margen, descuentos y comprobantes.',
          },
          analytics: {
            title: 'Gráficos y rankings',
            body: 'Aquí se comparan métodos de pago, categorías, documentos, productos y desempeño por cajero.',
          },
        },
        sales: {
          header: {
            title: 'Historial de ventas',
            body: 'Esta pantalla permite consultar comprobantes emitidos y revisar ventas anteriores.',
          },
          new_sale: {
            title: 'Nueva venta',
            body: 'Este botón vuelve al POS para iniciar otra venta presencial.',
          },
          filters: {
            title: 'Filtros de ventas',
            body: 'Busca por correlativo, fechas, cajero, tipo de documento, método de pago, estado o rango de monto.',
          },
          table: {
            title: 'Ventas emitidas',
            body: 'Cada fila muestra documento, fecha, cajero, tipo, pagos, total y estado.',
          },
          actions: {
            title: 'Acciones del comprobante',
            body: 'Desde acciones puedes ver detalle, abrir ticket térmico, descargar PDF o solicitar reimpresión.',
          },
        },
        employees: {
          header: {
            title: 'Gestión de empleados',
            body: 'Aquí el administrador registra cajeros y controla sus datos de acceso.',
          },
          create: {
            title: 'Crear cajero',
            body: 'Este botón abre el formulario para registrar un nuevo empleado con usuario del sistema.',
          },
          filters: {
            title: 'Buscar empleados',
            body: 'Filtra por nombre, correo, teléfono o documento para ubicar rápido a un cajero.',
          },
          table: {
            title: 'Listado de empleados',
            body: 'La tabla muestra rol, contacto, salario, estado y datos principales del cajero.',
          },
          actions: {
            title: 'Editar empleado',
            body: 'Usa estas acciones para corregir datos, actualizar contraseña o retirar un empleado.',
          },
        },
        catalog: {
          header: {
            title: 'Catálogo administrativo',
            body: 'Esta pantalla organiza datos base como categorías, unidades o proveedores.',
          },
          create: {
            title: 'Crear registro',
            body: 'Este botón abre el modal para crear un nuevo dato sin salir de la pantalla.',
          },
          filters: {
            title: 'Filtros del catálogo',
            body: 'Usa los filtros para encontrar categorías, unidades o proveedores por sus datos principales.',
          },
          table: {
            title: 'Listado del catálogo',
            body: 'Aquí se muestran los registros existentes con su estado y datos clave.',
          },
          actions: {
            title: 'Acciones del registro',
            body: 'Edita o elimina registros desde estos botones cuando necesites corregir la información.',
          },
        },
        settings: {
          header: {
            title: 'Configuración del negocio',
            body: 'Aquí se definen datos visibles en comprobantes, formatos y parámetros de operación.',
          },
          business: {
            title: 'Datos del negocio',
            body: 'Actualiza nombre comercial, documento, dirección, correo y logo usados por el sistema.',
          },
          format: {
            title: 'Formato regional',
            body: 'Controla moneda, símbolo, zona horaria, decimales, impuesto y descuento general.',
          },
          printing: {
            title: 'Comprobantes e impresión',
            body: 'Configura documento por defecto, ancho de papel, copias y comportamiento del ticket térmico.',
          },
          save: {
            title: 'Guardar configuración',
            body: 'Guarda al final para aplicar los cambios a ventas, comprobantes y reportes.',
          },
        },
        profile: {
          appearance: {
            title: 'Apariencia del sistema',
            body: 'Personaliza tema, idioma, contraste y preferencias visuales de tu cuenta.',
          },
          account: {
            title: 'Datos de cuenta',
            body: 'Actualiza nombre, usuario interno y contraseña manteniendo el dominio fijo de LaraTory.',
          },
          card: {
            title: 'Tarjeta de perfil',
            body: 'Esta tarjeta muestra tu foto, rol y datos visibles de usuario.',
          },
        },
        default: {
          header: {
            title: 'Pantalla actual',
            body: 'Este encabezado te ubica dentro del sistema y resume el objetivo de la sección.',
          },
        },
      },
    },
  },
  en: {
    help: {
      button: 'Help and tutorial',
      title: 'Help center',
      subtitle: 'Learn LaraTory with quick guides, frequent answers and visual walkthroughs.',
      open_manual: 'Manual',
      open_faq: 'Questions',
      open_tour: 'Guide',
      start_tour: 'Start this screen guide',
      restart_tour: 'Restart guide',
      no_tour: 'This screen uses the general system guide.',
      step_counter: '{current} of {total}',
      next: 'Next',
      previous: 'Previous',
      finish: 'Finish',
      skip: 'Skip',
      missing_target: 'This element is not visible right now. You can continue with the next step.',
      tip_title: 'Quick tip',
      tip_body: 'Use the help button when you switch screens to see a specific explanation.',
      tabs: {
        manual: 'Manual',
        faq: 'FAQ',
        tour: 'Usage guide',
      },
      manual: {
        pos: {
          title: 'Point of sale',
          body: 'Recommended flow for fast in-store sales.',
          step_search: 'Search by name or scan the barcode with an external reader.',
          step_cart: 'Add products to the cart and adjust quantities before charging.',
          step_pay: 'Charge with cash, Yape, Plin, card, transfer or mixed payment.',
          step_document: 'Choose receipt or invoice before confirming the sale.',
        },
        cash: {
          title: 'Register',
          body: 'The register controls shift cash and separates digital payments.',
          step_open: 'Open the register at the start with the drawer opening cash.',
          step_monitor: 'Review sales, income and expenses during the shift.',
          step_close: 'When closing, count physical cash and compare it against expected cash.',
        },
        inventory: {
          title: 'Inventory',
          body: 'Register clear products so the POS stays fast and reliable.',
          step_create: 'Create products with name, category, unit, price and stock.',
          step_barcode: 'Scan the code to save it without typing manually.',
          step_stock: 'Keep costs and stock updated for accurate reports.',
        },
        reports: {
          title: 'Reports',
          body: 'Use reports to understand sales, payment methods and profit.',
          step_filter: 'Filter by dates to review specific periods.',
          step_compare: 'Compare products, payment methods and cashier performance.',
          step_export: 'Download PDF or Excel when you need backups.',
        },
      },
      faq: {
        scanner: {
          question: 'How do I use a barcode reader?',
          answer: 'Keep the LaraTory window active. The reader types the code like a keyboard; when it finishes with Enter, the POS searches and adds the product.',
        },
        payments: {
          question: 'Can I charge with several methods?',
          answer: 'Yes. In the payment modal use mixed payment and split the total between cash, Yape, Plin, card or transfer.',
        },
        documents: {
          question: 'When do I use receipt or invoice?',
          answer: 'Use receipt for simple consumer sales and invoice when the customer needs tax support with RUC.',
        },
        roles: {
          question: 'What is the difference between administrator and cashier?',
          answer: 'The cashier sells and controls their register. The administrator manages inventory, employees, registers, reports and settings.',
        },
        stock: {
          question: 'What happens if a product has no stock?',
          answer: 'The POS marks it as unavailable to avoid sales without inventory. Update stock from products.',
        },
      },
      tours: {
        global: {
          commands: {
            title: 'Quick commands',
            body: 'Search any system section from this command. You can also use the / key.',
          },
          notifications: {
            title: 'Important alerts',
            body: 'Register, low-stock and pending-action alerts appear here.',
          },
          help: {
            title: 'Help always available',
            body: 'From this button you can repeat the screen tour, read the manual or solve frequent questions.',
          },
        },
        dashboard: {
          header: {
            title: 'Business summary',
            body: 'The dashboard shows sales, register, critical products and recent activity for quick decisions.',
          },
          period: {
            title: 'Analysis period',
            body: 'Change the month to recalculate metrics, charts and dashboard comparisons.',
          },
          hero: {
            title: 'Today result',
            body: 'This block summarizes income, profit and cost for the day with quick access to reports and sales.',
          },
          summary: {
            title: 'Main indicators',
            body: 'These cards show quick signals: sales, profit, active products and open registers.',
          },
          alerts: {
            title: 'Operational alerts',
            body: 'Cash differences, low stock or products without cost appear here so you can act quickly.',
          },
          analytics: {
            title: 'Visual analysis',
            body: 'These charts separate payment methods, best-selling products and products with the highest profit.',
          },
          trend: {
            title: 'Monthly trend',
            body: 'Compare the current period against last year and watch sales volume by month.',
          },
          recent: {
            title: 'Recent activity',
            body: 'Review recent sales before opening the full history.',
          },
          quick: {
            title: 'Quick access',
            body: 'Use these shortcuts to start a sale, review registers, create products or open reports.',
          },
        },
        pos: {
          search: {
            title: 'Search or scan',
            body: 'Type the product name or scan the barcode. If the product exists, it is added or shown in results.',
          },
          shortcuts: {
            title: 'Sale shortcuts',
            body: 'The POS accepts keyboard commands to search, charge and close modals without interrupting the cashier flow.',
          },
          register: {
            title: 'Register state',
            body: 'A register must be open before charging. The timer shows how long the shift has been active.',
          },
          products: {
            title: 'Available products',
            body: 'Each card shows photo, stock, price and code. Only this area should scroll when many products exist.',
          },
          product_card: {
            title: 'Product card',
            body: 'Press a card to add the product to the cart. If there is no stock, the system prevents selling it.',
          },
          cart: {
            title: 'Sale cart',
            body: 'Adjust quantities, remove products and verify the summary before charging.',
          },
          cart_item: {
            title: 'Quantity and removal',
            body: 'When products are in the cart, you can increase, reduce or remove each line before payment.',
          },
          pay: {
            title: 'Charge sale',
            body: 'This button opens the payment modal to choose document and payment method.',
          },
        },
        products: {
          header: {
            title: 'Product management',
            body: 'Register products, barcodes, prices, costs and images from here.',
          },
          workflow: {
            title: 'Guide to create a product',
            body: 'This walkthrough focuses on the real flow: open the modal, identify the product, classify it, set price/stock and save.',
          },
          create: {
            title: 'Open registration',
            body: 'Use this button to open the creation modal without leaving the screen.',
          },
          form_identity: {
            title: 'Identify the product',
            body: 'Start with barcode and name. If you scan a code, LaraTory keeps it and may complete data when it finds matches.',
          },
          form_classification: {
            title: 'Organize it for search',
            body: 'Select category and unit. Supplier is optional and useful when you want to remember where it is bought.',
          },
          form_stock_price: {
            title: 'Price, cost and stock',
            body: 'Complete cost, selling price and quantity. These values feed the POS, stock and profit reports.',
          },
          form_save: {
            title: 'Save and sell',
            body: 'After saving, the product is available for search or barcode scanning in the point of sale.',
          },
          summary: {
            title: 'Inventory state',
            body: 'These cards show total, available, low-stock, out-of-stock and products without cost.',
          },
          filters: {
            title: 'Product filters',
            body: 'Filter by name, barcode, category, stock or status to find a product quickly.',
          },
          table: {
            title: 'Product list',
            body: 'The table gathers photo, code, category, stock, cost, price and operational status.',
          },
          actions: {
            title: 'Edit or delete',
            body: 'Each row button lets you correct data or delete products when appropriate.',
          },
        },
        cash: {
          header: {
            title: 'Register state',
            body: 'Control open registers, closings and cash differences by employee.',
          },
          summary: {
            title: 'Register summary',
            body: 'These cards show how many registers are open, closed and how much cash is expected.',
          },
          filters: {
            title: 'Filter by employee',
            body: 'As administrator you can filter by cashier or state to find a specific register.',
          },
          employee_board: {
            title: 'Employee board',
            body: 'Each card shows the employee register state, sales, expected cash and active time.',
          },
          status: {
            title: 'Quick register reading',
            body: 'Colors help distinguish open, closed or difference states.',
          },
          actions: {
            title: 'Close registers',
            body: 'If you are administrator, you can close employee registers and leave the closing audited.',
          },
        },
        reports: {
          header: {
            title: 'Reports',
            body: 'Review sales, profit, payments and rankings without mixing operational data.',
          },
          filters: {
            title: 'Period filters',
            body: 'Change dates to update charts and exports.',
          },
          summary: {
            title: 'Report totals',
            body: 'These cards summarize income, costs, profit, margin, discounts and documents.',
          },
          analytics: {
            title: 'Charts and rankings',
            body: 'Payment methods, categories, documents, products and cashier performance are compared here.',
          },
        },
        sales: {
          header: {
            title: 'Sales history',
            body: 'This screen lets you consult issued documents and review previous sales.',
          },
          new_sale: {
            title: 'New sale',
            body: 'This button returns to the POS to start another in-store sale.',
          },
          filters: {
            title: 'Sales filters',
            body: 'Search by correlative, dates, cashier, document type, payment method, status or amount range.',
          },
          table: {
            title: 'Issued sales',
            body: 'Each row shows document, date, cashier, type, payments, total and status.',
          },
          actions: {
            title: 'Document actions',
            body: 'From actions you can view detail, open thermal ticket, download PDF or request reprint.',
          },
        },
        employees: {
          header: {
            title: 'Employee management',
            body: 'Here the administrator registers cashiers and controls their access data.',
          },
          create: {
            title: 'Create cashier',
            body: 'This button opens the form to register a new employee with system access.',
          },
          filters: {
            title: 'Search employees',
            body: 'Filter by name, email, phone or document to quickly find a cashier.',
          },
          table: {
            title: 'Employee list',
            body: 'The table shows role, contact, salary, status and main cashier data.',
          },
          actions: {
            title: 'Edit employee',
            body: 'Use these actions to correct data, update password or remove an employee.',
          },
        },
        catalog: {
          header: {
            title: 'Administrative catalog',
            body: 'This screen organizes base data such as categories, units or suppliers.',
          },
          create: {
            title: 'Create record',
            body: 'This button opens the modal to create a new record without leaving the screen.',
          },
          filters: {
            title: 'Catalog filters',
            body: 'Use filters to find categories, units or suppliers by their main data.',
          },
          table: {
            title: 'Catalog list',
            body: 'Existing records are shown here with their state and key data.',
          },
          actions: {
            title: 'Record actions',
            body: 'Edit or delete records from these buttons when information needs correction.',
          },
        },
        settings: {
          header: {
            title: 'Business settings',
            body: 'Visible document data, formats and operating parameters are defined here.',
          },
          business: {
            title: 'Business data',
            body: 'Update trade name, document, address, email and logo used by the system.',
          },
          format: {
            title: 'Regional format',
            body: 'Control currency, symbol, timezone, decimals, tax and general discount.',
          },
          printing: {
            title: 'Documents and printing',
            body: 'Configure default document, paper width, copies and thermal ticket behavior.',
          },
          save: {
            title: 'Save settings',
            body: 'Save at the end to apply changes to sales, documents and reports.',
          },
        },
        profile: {
          appearance: {
            title: 'System appearance',
            body: 'Customize theme, language, contrast and visual preferences for your account.',
          },
          account: {
            title: 'Account data',
            body: 'Update name, internal user and password while keeping the LaraTory domain fixed.',
          },
          card: {
            title: 'Profile card',
            body: 'This card shows your photo, role and visible user data.',
          },
        },
        default: {
          header: {
            title: 'Current screen',
            body: 'This header locates you in the system and summarizes the purpose of the section.',
          },
        },
      },
    },
  },
};
