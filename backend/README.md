# Backend PHP - Yober Sport

Backend en PHP con MySQL para el e-commerce Yober Sport.

## Requisitos

- PHP 8.0 o superior
- MySQL 8.0 o superior
- Servidor web (Apache con mod_rewrite o Nginx)

## Instalación

1. **Configurar la base de datos:**

   ```bash
   mysql -u root -p < database.sql
   ```

   O ejecuta el contenido de `database.sql` en tu cliente MySQL favorito (phpMyAdmin, DBeaver, etc.)

2. **Configurar variables de entorno:**

   Copia `.env.example` a `.env` y configura tus credenciales de MySQL:

   ```bash
   cp .env.example .env
   ```

   Edita `.env`:
   ```
   DB_HOST=localhost
   DB_NAME=yober_sport
   DB_USER=tu_usuario
   DB_PASSWORD=tu_password
   ```

3. **Configurar el servidor web:**

   - **Apache:** Asegúrate de que `mod_rewrite` esté habilitado
   - **Nginx:** Configura el routing para redirigir a `index.php`

   Document root debe apuntar a la carpeta `backend/`

4. **Permisos:**

   Asegúrate de que PHP tenga permisos de escritura en la carpeta (para sesiones si es necesario).

## API Endpoints

### Autenticación

- `POST /api/auth.php?action=register` - Registrar usuario
  ```json
  {
    "email": "usuario@ejemplo.com",
    "password": "password123",
    "name": "Nombre Usuario"
  }
  ```

- `POST /api/auth.php?action=login` - Iniciar sesión
  ```json
  {
    "email": "usuario@ejemplo.com",
    "password": "password123"
  }
  ```

- `POST /api/auth.php?action=logout` - Cerrar sesión

- `GET /api/auth.php?action=me` - Obtener usuario actual

### Productos

- `GET /api/products.php` - Listar productos (query params: `first`, `handle`, `id`)
- `POST /api/products.php` - Crear producto (requiere login)
- `PUT /api/products.php` - Actualizar producto (requiere login)
- `DELETE /api/products.php?id=X` - Eliminar producto (requiere login)

### Carrito

- `GET /api/cart.php` - Obtener carrito actual
- `POST /api/cart.php?action=create` - Crear carrito
- `POST /api/cart.php?action=add` - Agregar items al carrito
  ```json
  {
    "lines": [
      {
        "merchandiseId": "gid://shopify/ProductVariant/1",
        "quantity": 1
      }
    ]
  }
  ```
- `PUT /api/cart.php` - Actualizar cantidad de items
- `DELETE /api/cart.php?lineIds=id1,id2` - Eliminar items del carrito

### Categorías

- `GET /api/categories.php` - Listar categorías activas (iconos incluidos)
- `GET /api/categories.php?all=1` - Listar todas (admin)
- `POST /api/categories.php` - Crear categoría (requiere login)
  ```json
  {
    "name": "Hoodies",
    "slug": "hoodies",
    "icon_url": "https://ejemplo.com/icono.png",
    "sort_order": 1,
    "is_active": true
  }
  ```
- `PUT /api/categories.php` - Actualizar categoría (requiere login)
- `DELETE /api/categories.php?id=X` - Eliminar categoría (requiere login)

Panel admin: `/admin/categories.php` — ahí se sube o pega la URL del icono.

### Variantes

- `POST /api/variants.php` - Crear variante (requiere login)
  ```json
  {
    "product_id": 1,
    "title": "M",
    "price": 89000,
    "inventory": 10,
    "sku": "UHB-M"
  }
  ```

### Imágenes

- `POST /api/images.php` - Agregar imagen (requiere login)
  ```json
  {
    "product_id": 1,
    "url": "https://ejemplo.com/imagen.jpg",
    "alt_text": "Descripción"
  }
  ```

## Panel Administrativo (completo)

1. Abre una vez: `/backend/admin/setup.php` (crea tablas + admin)
2. Entra en: `/backend/admin/login.php`

Credenciales tras setup:
- Email: `admin@yolbersport.com`
- Password: `admin123`

El panel permite gestionar:
- **Dashboard** — métricas, stock bajo, pedidos recientes
- **Productos** — crear/editar, categorías, variantes, stock, imágenes
- **Categorías** — iconos del carrusel
- **Pedidos** — estados, detalle, pedidos manuales, WhatsApp
- **Usuarios** — altas, roles admin
- **Configuración** — WhatsApp, contacto, nombre de tienda

Frontend: en `.env` configura `VITE_API_URL` y `VITE_WHATSAPP_PHONE`, y en `src/lib/shopify.ts` pon `USE_MOCK_DATA = false`.

## Notas

- Los carritos funcionan con session IDs sin login, pero pueden asociarse a usuarios cuando hacen login
- La API usa formato compatible con Shopify para facilitar la migración desde el frontend existente
- El CORS está habilitado para permitir peticiones desde cualquier origen (configurable en `includes/functions.php`)
- Las contraseñas se hashean usando `password_hash()` de PHP

## Estructura de Archivos

```
backend/
├── api/
│   ├── auth.php          # Autenticación
│   ├── products.php      # Productos
│   ├── cart.php          # Carrito
│   ├── variants.php      # Variantes
│   └── images.php        # Imágenes
├── admin/
│   ├── login.php         # Login admin
│   ├── index.php         # Panel principal
│   ├── edit.php          # Editar producto
│   └── logout.php        # Logout
├── config/
│   └── database.php      # Conexión a BD
├── includes/
│   └── functions.php     # Funciones helper
├── database.sql          # Schema de BD
├── .htaccess             # Routing
├── .env.example          # Variables de entorno
└── index.php             # Redirect al frontend
```
