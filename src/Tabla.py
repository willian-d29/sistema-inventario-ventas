import pandas as pd
from faker import Faker
import random

fake = Faker('es_ES')
Faker.seed(42)

# Todas las categorías definidas
categorias = {
    'Alimentos': [
        'Pan integral', 'Queso andino', 'Yogurt natural', 'Arroz en grano', 'Galletas artesanales',
        'Leche descremada', 'Mantequilla fresca', 'Frutas secas', 'Café molido', 'Té verde',
        'Aceite de oliva', 'Sal marina', 'Azúcar rubia', 'Fideos de trigo', 'Salsa de tomate',
        'Harina integral', 'Vinagre de manzana', 'Chocolate orgánico', 'Avena instantánea', 'Miel de abeja'
    ],
    'Ropa': [
        'Camisa de algodón', 'Pantalón jeans', 'Chaqueta impermeable', 'Vestido de verano',
        'Sudadera deportiva', 'Falda casual', 'Polo clásico', 'Blusa estampada', 'Abrigo largo',
        'Chompa tejida', 'Ropa interior', 'Calcetines térmicos', 'Gorro de lana', 'Bufanda de alpaca',
        'Chaqueta de cuero', 'Pantalones cortos', 'Chaleco acolchado', 'Leggins deportivos',
        'Camisa formal', 'Buzo polar'
    ],
    'Electronica': [
        'Laptop HP', 'Tablet Samsung', 'Auriculares Bluetooth', 'Monitor LG 24"', 'Cámara digital',
        'Smartwatch Xiaomi', 'Teclado mecánico', 'Mouse inalámbrico', 'Cargador portátil', 'Router Wi-Fi',
        'Memoria USB 64GB', 'Disco duro externo', 'Impresora multifunción', 'Parlante portátil',
        'Micrófono profesional', 'Webcam HD', 'Pantalla táctil', 'Batería recargable', 'Proyector LED',
        'Control remoto universal'
    ],
    'Hogar': [
        'Mesa de comedor', 'Silla ergonómica', 'Cortinas blackout', 'Lámpara LED', 'Colchón ortopédico',
        'Espejo decorativo', 'Alfombra moderna', 'Cojines suaves', 'Jarrón cerámico', 'Reloj de pared',
        'Estantería de madera', 'Ropero modular', 'Toallas absorbentes', 'Mueble de TV', 'Sábanas de algodón',
        'Cobertor térmico', 'Organizador de zapatos', 'Perchero de pie', 'Ventilador silencioso',
        'Cortina de baño'
    ],
    'Juguetes': [
        'Muñeca articulada', 'Auto a control remoto', 'Bloques de construcción', 'Puzzle educativo',
        'Juego de mesa', 'Pelota saltarina', 'Set de plastilina', 'Rompecabezas 3D', 'Juguete musical',
        'Pistola de agua', 'Carrito de bomberos', 'Cubo mágico', 'Peluche gigante', 'Aros encajables',
        'Robot interactivo', 'Juego de memoria', 'Camión de carga', 'Títeres de mano', 'Dominó infantil',
        'Maletín veterinario de juguete'
    ],
    'Libros': [
        'Novela romántica', 'Manual de cocina', 'Libro de aventuras', 'Enciclopedia ilustrada',
        'Cuentos infantiles', 'Biografía histórica', 'Libro de autoayuda', 'Libro de ciencia ficción',
        'Ensayo filosófico', 'Diccionario escolar', 'Libro de poesía', 'Atlas geográfico', 'Libro de matemáticas',
        'Crónica periodística', 'Historieta educativa', 'Fábulas clásicas', 'Manga japonés', 'Guía turística',
        'Libro de colorear', 'Revista educativa'
    ],
    'Belleza': [
        'Crema facial hidratante', 'Perfume floral', 'Shampoo nutritivo', 'Maquillaje compacto',
        'Esmalte de uñas', 'Gel fijador', 'Toallitas desmaquillantes', 'Mascarilla capilar', 'Peine antienredo',
        'Crema antiarrugas', 'Bálsamo labial', 'Aceite de coco', 'Set de brochas', 'Exfoliante corporal',
        'Loción corporal', 'Espuma limpiadora', 'Crema para manos', 'Tónico facial', 'Desodorante natural',
        'Sombras de ojos'
    ],
    'Deportes': [
        'Pelota de fútbol', 'Raqueta de tenis', 'Colchoneta de yoga', 'Guantes de boxeo',
        'Camiseta deportiva', 'Bicicleta de montaña', 'Zapatillas running', 'Gorra deportiva',
        'Botella de agua', 'Cuerda para saltar', 'Rodillera deportiva', 'Muñequeras elásticas',
        'Bolso deportivo', 'Protector bucal', 'Pesa rusa', 'Balón de baloncesto', 'Casco de ciclismo',
        'Red de voleibol', 'Chaleco reflectante', 'Gafas deportivas'
    ],
    'Herramientas': [
        'Martillo reforzado', 'Taladro inalámbrico', 'Cinta métrica', 'Caja de herramientas',
        'Destornillador eléctrico', 'Sierra manual', 'Nivel de burbuja', 'Llave inglesa',
        'Juego de brocas', 'Clavos variados', 'Alicate multifunción', 'Soldador eléctrico',
        'Lijadora orbital', 'Remachadora', 'Cúter retráctil', 'Multímetro digital',
        'Guantes anticorte', 'Mazo de goma', 'Cepillo de acero', 'Lupa con luz'
    ],
    'Accesorios': [
        'Reloj deportivo', 'Gorra de béisbol', 'Mochila urbana', 'Cinturón de cuero',
        'Bolso cruzado', 'Lentes de sol', 'Pañuelo estampado', 'Guantes de invierno',
        'Cartera compacta', 'Bandolera casual', 'Riñonera moderna', 'Bufanda de alpaca',
        'Llavero decorativo', 'Funda para celular', 'Estuche de gafas', 'Gorro tejido',
        'Pinza para billetes', 'Tirantes elegantes', 'Diadema floral', 'Collar artesanal'
    ]
}

unidad_por_categoria = {
    'Alimentos': 'kg',
    'Ropa': 'pz',
    'Electronica': 'pz',
    'Hogar': 'pz',
    'Juguetes': 'pz',
    'Libros': 'u',
    'Belleza': 'ml',
    'Deportes': 'pz',
    'Herramientas': 'pz',
    'Accesorios': 'pz'
}

productos = []

for categoria, lista_productos in categorias.items():
    for base in lista_productos:
        atributo = fake.word().capitalize()
        extra = str(random.randint(100, 999))
        nombre = f"{base} - {atributo} {extra}"
        descripcion = f"{base} ideal para {fake.word()} y actividades cotidianas. Incluye características como {fake.word()} y {fake.word()}."

        buying_price = round(random.uniform(10.0, 300.0), 2)
        selling_price = round(buying_price + random.uniform(20.0, 150.0), 2)

        producto = {
            'name': nombre,
            'description': descripcion,
            'category': categoria,
            'buying_price': buying_price,
            'selling_price': selling_price,
            'quantity': random.randint(10, 100),
            'unit_type': unidad_por_categoria[categoria],
            'photo': f"{categoria.lower()}.jpg",
            'supplier_name': fake.company()
        }

        productos.append(producto)

df = pd.DataFrame(productos)
import os
from pathlib import Path
output_path = Path("productos.csv")

df.to_csv(output_path, index=False, encoding="utf-8", lineterminator="\n")
output_path.name
